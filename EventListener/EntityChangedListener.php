<?php

namespace HistorizationBundle\EventListener;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use HistorizationBundle\Annotation\Config;
use HistorizationBundle\Annotation\JoinConfig;
use HistorizationBundle\Annotation\HistorizationDisplayName;
use HistorizationBundle\Entity\ChangeLogHistory;
use HistorizationBundle\Entity\UpdateSet;
use HistorizationBundle\Helper\TypeFilter;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Doctrine\Common\Annotations\AnnotationException;

/**
 * Class EntityChangedListener
 * @package HistorizationBundle\EventListener
 */
class EntityChangedListener
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $insertionCount;

    /** @var int */
    private $updateCount;

    /** @var int  */
    private $deletionCount;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->insertionCount = 0;
        $this->updateCount = 0;
        $this->deletionCount = 0;
        $this->allTokens = new \SplObjectStorage;
    }

    /**
     * Logging on CRUD Operation for
     * Entities with Annotation
     * // @Config(
     * //     historizable="true"
     * // )
     *
     * @param OnFlushEventArgs $eventArgs
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();

        $this->findHistorizationInsertions($em);
        $this->findHistorizationUpdates($em);
        $this->findHistorizationDeletions($em);
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $this->logger->info(sprintf('HistorizationBundle - Change Log History Records Created for %s Insertions, %s Updates, %s Deletions', $this->insertionCount, $this->updateCount, $this->deletionCount));
    }

    /**
     * Create Historization Log Record for 'Create' Operations
     *
     * @param EntityManager $em
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    private function findHistorizationInsertions(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->isHistorizable($entity)) {
                continue;
            }

            $updateChanges = $uow->getEntityChangeSet($entity);

            if (!empty($updateChanges)) {
                $classId = $entity->getId();
                $className = ClassUtils::getClass($entity);
                
                if (method_exists($this->getSecurityToken($em), 'getUser')) {
                    $userName = $this->getSecurityToken($em)->getUser()->getUserName();
                } else {
                    $userName = "Command";
                }

                $changeLogHistory = new ChangeLogHistory($classId, ChangeLogHistory::ACTION_TYPE_INSERT, $className, $userName);

                $changeLogHistoryMetaData = $em->getClassMetadata(get_class($changeLogHistory));
                $em->persist($changeLogHistory);
                $uow->computeChangeSet($changeLogHistoryMetaData, $changeLogHistory);

                $this->insertionCount += 1;
            }
        }
    }

    /**
     * Create Historization Log Record for 'Update' Operations
     *
     * @param EntityManager $em
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    private function findHistorizationUpdates(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->isHistorizable($entity)) {
                continue;
            }

            $classId = $entity->getId();
            $className = ClassUtils::getClass($entity);

            if (method_exists($this->getSecurityToken($em), 'getUser')) {
                $userName = $this->getSecurityToken($em)->getUser()->getUserName();
            } else {
                $userName = "Command";
            }

            $changeLogHistory = new ChangeLogHistory($classId, ChangeLogHistory::ACTION_TYPE_UPDATE, $className, $userName);

            $updateChanges = $uow->getEntityChangeSet($entity);

            $typeFilterHelper = new TypeFilter();
            foreach ($updateChanges as $columnName => $value) {
                $firstValue = $this->transformValues(current($value));
                $lastValue = $this->transformValues(end($value));

                $oldValue = $typeFilterHelper->filterTypes($firstValue);
                $newValue = $typeFilterHelper->filterTypes($lastValue);

                if ($oldValue == $newValue) {
                    continue;
                }

                $chosenConnectionColumnName = $this->isConnectionColumn($entity, $columnName);
                
                if ($chosenConnectionColumnName) {
                    $desiredValueOfConnectedEntity = $this->getDesiredValueOfConnectionEntity($firstValue, $chosenConnectionColumnName);

                    $oldValue = $typeFilterHelper->filterTypes($this->getDesiredValueOfConnectionEntity($firstValue, $chosenConnectionColumnName));
                    $newValue = $typeFilterHelper->filterTypes($this->getDesiredValueOfConnectionEntity($lastValue, $chosenConnectionColumnName));
                }

                $columnName = $this->getDisplayName($entity, $columnName);

                $changeSet = new UpdateSet(ucfirst($columnName), $changeLogHistory, $oldValue, $newValue);

                $changeSetMetaData = $em->getClassMetadata(get_class($changeSet));
                $em->persist($changeSet);
                $uow->computeChangeSet($changeSetMetaData, $changeSet);

                $changeLogHistory->addUpdateSet($changeSet);

                $changeLogHistoryMetaData = $em->getClassMetadata(get_class($changeLogHistory));
                $em->persist($changeLogHistory);
                $uow->computeChangeSet($changeLogHistoryMetaData, $changeLogHistory);

                $this->updateCount +=1;
            }
        }
    }

    /**
     * Create Historization Log Record for 'Delete' Operations
     *
     * @param EntityManager $em
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    private function findHistorizationDeletions(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->isHistorizable($entity)) {
                continue;
            }

            $classId = $entity->getId();
            $className = ClassUtils::getClass($entity);

            if (method_exists($this->getSecurityToken($em), 'getUser')) {
                $userName = $this->getSecurityToken($em)->getUser()->getUserName();
            } else {
                $userName = "Command";
            }

            // Create Delete Log
            $changeLogHistory = new ChangeLogHistory($classId, ChangeLogHistory::ACTION_TYPE_DELETE, $className, $userName);

            $changeLogHistoryMetaData = $em->getClassMetadata(get_class($changeLogHistory));
            $em->persist($changeLogHistory);
            $uow->computeChangeSet($changeLogHistoryMetaData, $changeLogHistory);

            $this->deletionCount +=1;
        }
    }

    /**
     * Annotation check if entity is historizable
     *
     * @param $entity
     * @return bool
     */
    private function isHistorizable($entity)
    {
        try {
            $reflClass = new \ReflectionClass(get_class($entity));
            $annotationReader = new AnnotationReader();
        } catch (\ReflectionException $exception) {
            return false;
        } catch (AnnotationException $exception) {
            return false;
        }

        $classAnnotations = $annotationReader->getClassAnnotations($reflClass);
        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof Config && $classAnnotation->getHistorizable() === "true") {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the name in 'HistorizationDisplayName' property annotation If found
     *
     * @param $entity
     * @param $columnName
     * @return bool|mixed|string
     */
    private function getDisplayName($entity, $columnName)
    {
        try {
            $reflProperty = new \ReflectionProperty(get_class($entity), $columnName);
            $annotationReader = new AnnotationReader();
        } catch (\ReflectionException $exception) {
            return $columnName;
        } catch (AnnotationException $exception) {
            return $columnName;
        } catch (\Exception $exception) {
            return $columnName;
        }

        $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflProperty);

        foreach ($propertyAnnotations as $index => $propertyAnnotation) {
            if ($propertyAnnotation instanceof HistorizationDisplayName) {
                $chosenDisplayName = $propertyAnnotation->getHistorizationDisplayName();

                return $chosenDisplayName;
            }
        }

        return $columnName;
    }

    /**
     * Annotation check if property is join column and returns desired column name on connection table
     *
     * @param $entity
     * @param $columnName
     * @return bool|mixed|string
     */
    private function isConnectionColumn($entity, $columnName)
    {
        try {
            $reflProperty = new \ReflectionProperty(get_class($entity), $columnName);
            $annotationReader = new AnnotationReader();
        } catch (\ReflectionException $exception) {
            return false;
        } catch (AnnotationException $exception) {
            return false;
        }

        $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflProperty);

        foreach ($propertyAnnotations as $index => $propertyAnnotation) {
            if ($propertyAnnotation instanceof JoinConfig) {
                $desiredConnectedColumnName = $propertyAnnotation->getJoinTableColumnName();

                return $desiredConnectedColumnName;
            }
        }

        return false;
    }

    /**
     * Returns value of property for given entity
     *
     * @param $entity
     * @param $desiredConnectedColumnName
     * @return mixed
     */
    private function getDesiredValueOfConnectionEntity($entity, $desiredConnectedColumnName)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $desiredConnectedColumnValue = $propertyAccessor->getValue($entity, $desiredConnectedColumnName);

        return $desiredConnectedColumnValue;
    }

    /**
     * @param EntityManager $em
     *
     * @return TokenInterface|null
     */
    private function getSecurityToken(EntityManager $em)
    {
        return $this->allTokens->contains($em)
            ? $this->allTokens[$em]
            : $this->tokenStorage->getToken();
    }

    /**
     * @param $value
     * @return string
     */
    private function transformValues($value)
    {
        if (is_bool($value)) {
            if ($value === true) {
                $value = 'Yes';
            }

            if ($value === false) {
                $value = 'No';
            }
        }

        return $value;
    }

}
