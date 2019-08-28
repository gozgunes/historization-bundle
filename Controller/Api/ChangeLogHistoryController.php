<?php

namespace HistorizationBundle\Controller\Api;

use HistorizationBundle\Service\HistorizationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class ChangeLogHistoryController
 * @package HistorizationBundle\Controller\Api
 */
class ChangeLogHistoryController extends Controller
{
    /**
     * @Route("/getHistorizationRecordsOfUser", name="historization_bundle_get_change_log_records_of_user")
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     */
    public function getHistorizationRecordsByUser()
    {
        /** @var HistorizationService $historizationService */
        $historizationService = $this->container->get('historization_bundle.service.historization_service');
        $historyLogs = $historizationService->findChangeLogHistoryRecordsByUser();

        $response = ['changeLog' => $historyLogs];

        return new JsonResponse($response);
    }

    /**
     * @Route("/getHistorizationRecordsOfEntity", name="historization_bundle_get_change_log_records_of_entity")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistorizationRecordsByEntity(Request $request)
    {
        $entity = $request->request->get('entity');

        $entityClass = get_class($entity);
        $entityId = $entity->getId();

        /** @var HistorizationService $historizationService */
        $historizationService = $this->container->get('historization_bundle.service.historization_service');

        $historyLogs = $historizationService->findChangeLogHistoryRecordsByEntity($entityClass, $entityId);

        $response = ['changeLog' => $historyLogs];

        return new JsonResponse($response);
    }

    /**
     * @Route("/getHistorizationRecordsOfUserView", name="historization_bundle_get_change_log_records_of_user_view")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     */
    public function getHistorizationRecordsByUserView()
    {
        /** @var HistorizationService $historizationService */
        $historizationService = $this->container->get('historization_bundle.service.historization_service');
        $historyLogs = $historizationService->findChangeLogHistoryRecordsByUser();

        return $this->render('HistorizationBundle:Default:_index.html.twig', [
            'HistoryLogs' => $historyLogs
        ]);
    }

    /**
     * @Route("/getHistorizationRecordsOfEntityView", name="historization_bundle_get_change_log_records_of_entity_view")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getHistorizationRecordsByEntityView($entity)
    {
        $entityClass = get_class($entity);
        $entityId = $entity->getId();

        /** @var HistorizationService $historizationService */
        $historizationService = $this->container->get('historization_bundle.service.historization_service');

        $historyLogs = $historizationService->findChangeLogHistoryRecordsByEntity($entityClass, $entityId);

        return $this->render('HistorizationBundle:Default:_index.html.twig', [
            'HistoryLogs' => $historyLogs
        ]);
    }

    /**
     * @Route("/getHistorizationRecordsOfEntityClassView", name="historization_bundle_get_change_log_records_of_entity_class_view")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getHistorizationRecordsByEntityClassView(string $entityClass)
    {
        /** @var HistorizationService $historizationService */
        $historizationService = $this->container->get('historization_bundle.service.historization_service');

        $historyLogs = $historizationService->findChangeLogHistoryRecordsByEntityClass($entityClass);

        return $this->render('HistorizationBundle:Default:_index.html.twig', [
            'HistoryLogs' => $historyLogs
        ]);
    }
}
