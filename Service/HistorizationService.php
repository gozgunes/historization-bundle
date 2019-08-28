<?php

namespace HistorizationBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use HistorizationBundle\Repository\ChangeLogHistoryRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;


/**
 * Class HistorizationService
 * @package HistorizationBundle\Service
 */
class HistorizationService
{
    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param TokenStorageInterface $securityTokenStorage
     * @param ManagerRegistry $doctrine
     */
    public function __construct(TokenStorageInterface $securityTokenStorage, ManagerRegistry $doctrine)
    {
        $this->securityTokenStorage = $securityTokenStorage;
        $this->doctrine = $doctrine;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    public function findChangeLogHistoryRecordsByUser()
    {
        $userName = $this->getUser()->getUsername();

        /** @var ChangeLogHistoryRepository $changeLogHistoryRepository */
        $changeLogHistoryRepository = $this->getEntityManager()->getRepository('HistorizationBundle\Entity\ChangeLogHistory');
        $changeLogHistoryRecords = $changeLogHistoryRepository->findBy(['username' => $userName], ['id' => 'DESC']);

        return $changeLogHistoryRecords;
    }

    /**
     * @param string $className
     * @param string $classId
     * @return array
     */
    public function findChangeLogHistoryRecordsByEntity(string $className, $classId): array
    {
        /** @var ChangeLogHistoryRepository $changeLogHistoryRepository */
        $changeLogHistoryRepository = $this->getEntityManager()->getRepository('HistorizationBundle\Entity\ChangeLogHistory');
        $changeLogHistoryRecords = $changeLogHistoryRepository->findBy(['className' => $className, 'classId' => $classId], ['id' => 'DESC']);

        return $changeLogHistoryRecords;
    }

    /**
     * @param string $className
     * @return array
     */
    public function findChangeLogHistoryRecordsByEntityClass(string $className): array
    {
        /** @var ChangeLogHistoryRepository $changeLogHistoryRepository */
        $changeLogHistoryRepository = $this->getEntityManager()->getRepository('HistorizationBundle\Entity\ChangeLogHistory');
        $changeLogHistoryRecords = $changeLogHistoryRepository->findBy(['className' => $className], ['id' => 'DESC']);

        return $changeLogHistoryRecords;
    }

    /**
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getUser()
    {
        $token = $this->securityTokenStorage->getToken();
        if (null === $token) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return null;
        }

        $className = ClassUtils::getClass($user);

        $id = $this->getEntityManager()->getClassMetadata($className)->getIdentifierValues($user);
        $id = reset($id);

        $user = $this->getEntityManager()->getReference($className, $id);

        return $user;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
