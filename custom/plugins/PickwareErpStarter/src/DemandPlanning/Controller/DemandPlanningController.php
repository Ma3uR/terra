<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning\Controller;

use Pickware\PickwareErpStarter\DemandPlanning\DemandPlanningCalculationService;
use Pickware\PickwareErpStarter\DemandPlanning\DemandPlanningListService;
use Pickware\PickwareErpStarter\DemandPlanning\DemandPlanningSessionService;
use Pickware\PickwareErpStarter\DemandPlanning\Model\DemandPlanningSessionDefinition;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Framework\Api\Response\ResponseFactoryRegistry;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemandPlanningController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DemandPlanningCalculationService
     */
    private $calculationService;

    /**
     * @var DemandPlanningSessionService
     */
    private $sessionService;

    /**
     * @var DemandPlanningListService
     */
    private $listService;

    /**
     * @var ResponseFactoryRegistry
     */
    private $responseFactoryRegistry;

    public function __construct(
        EntityManager $entityManager,
        DemandPlanningCalculationService $calculationService,
        DemandPlanningListService $listService,
        DemandPlanningSessionService $sessionService,
        ResponseFactoryRegistry $responseFactoryRegistry
    ) {
        $this->entityManager = $entityManager;
        $this->calculationService = $calculationService;
        $this->listService = $listService;
        $this->sessionService = $sessionService;
        $this->responseFactoryRegistry = $responseFactoryRegistry;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/demand-planning/ensure-session-exists",
     *     methods={"POST"}
     * )
     */
    public function ensureSessionExists(Request $request, Context $context): Response
    {
        $userId = $request->get('userId');
        if (!$userId || !Uuid::isValid($userId)) {
            return $this->createUuidParameterMissingResponse('userId');
        }

        $sessionId = $this->sessionService->ensureSessionExists($userId, $context);
        $this->sessionService->clearOutdatedDemand();

        $session = $this->entityManager->findByPrimaryKey(
            DemandPlanningSessionDefinition::class,
            $sessionId,
            $context
        );
        $responseFactory = $this->responseFactoryRegistry->getType($request);

        return $responseFactory->createDetailResponse(
            new Criteria(),
            $session,
            $this->entityManager->getEntityDefinition(DemandPlanningSessionDefinition::class),
            $request,
            $context
        );
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/demand-planning/calculate-demand",
     *     methods={"POST"}
     * )
     */
    public function calculateDemand(Request $request, Context $context): Response
    {
        $sessionId = $request->get('sessionId');
        if (!$sessionId || !Uuid::isValid($sessionId)) {
            return $this->createUuidParameterMissingResponse('sessionId');
        }

        $this->calculationService->calculateDemand($sessionId, $context);

        return new JsonResponse();
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/demand-planning/add-items-to-purchase-list",
     *     methods={"POST"}
     * )
     */
    public function addItemsToPurchaseList(Request $request): Response
    {
        $demandPlanningListItemIds = $request->get('demandPlanningListItemIds', []);

        $this->listService->addDemandPlanningItemsToPurchaseList($demandPlanningListItemIds);

        return new JsonResponse();
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/demand-planning/add-all-items-to-purchase-list",
     *     methods={"POST"}
     * )
     */
    public function addAllItemsToPurchaseList(Request $request): Response
    {
        $sessionId = $request->get('sessionId');
        if (!$sessionId || !Uuid::isValid($sessionId)) {
            return $this->createUuidParameterMissingResponse('sessionId');
        }
        $listQuery = $request->get('listQuery', []);

        $this->listService->addAllItemsToPurchaseList($sessionId, $listQuery);

        return new JsonResponse();
    }

    private function createUuidParameterMissingResponse($parameterName): JsonResponse
    {
        return new JsonResponse(
            [
                'success' => false,
                'message' => sprintf('Parameter %s is missing or is not a UUID.', $parameterName),
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }
}
