<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderShipping\Controller;

use Pickware\PickwareErpStarter\OrderShipping\OrderShippingException;
use Pickware\PickwareErpStarter\OrderShipping\OrderShippingService;
use Pickware\HttpUtils\JsonApi\JsonApiErrorResponse;
use Pickware\HttpUtils\JsonApi\JsonApiErrors;
use Pickware\PickwareErpStarter\StockApi\StockMovementServiceValidationException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderShippingController
{
    /**
     * @var OrderShippingService
     */
    private $orderShippingService;

    public function __construct(OrderShippingService $orderShippingService)
    {
        $this->orderShippingService = $orderShippingService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/ship-order-completely",
     *     name="api.action.pickware-erp.ship-order-completely",
     *     methods={"POST"}
     * )
     */
    public function shipOrderCompletely(Request $request, Context $context): Response
    {
        $warehouseId = $request->get('warehouseId');
        if (!$warehouseId || !Uuid::isValid($warehouseId)) {
            return $this->createUuidParameterMissingResponse('warehouseId');
        }
        $orderId = $request->get('orderId');
        if (!$orderId || !Uuid::isValid($orderId)) {
            return $this->createUuidParameterMissingResponse('orderId');
        }

        try {
            $this->orderShippingService->shipOrderCompletely($orderId, $warehouseId, $context);
        } catch (OrderShippingException $e) {
            $jsonApiError = $e->serializeToJsonApiError();
            $jsonApiError->setStatus((string) Response::HTTP_CONFLICT);

            return new JsonApiErrorResponse(new JsonApiErrors([$jsonApiError]));
        }

        return new Response('', Response::HTTP_OK);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/return-order-completely",
     *     name="api.action.pickware-erp.return-order-completely",
     *     methods={"POST"}
     * )
     */
    public function returnOrderCompletely(Request $request, Context $context): Response
    {
        $warehouseId = $request->get('warehouseId');
        if (!$warehouseId || !Uuid::isValid($warehouseId)) {
            return $this->createUuidParameterMissingResponse('warehouseId');
        }
        $orderId = $request->get('orderId');
        if (!$orderId || !Uuid::isValid($orderId)) {
            return $this->createUuidParameterMissingResponse('orderId');
        }

        try {
            $this->orderShippingService->returnOrderCompletely($orderId, $warehouseId, $context);
        } catch (StockMovementServiceValidationException $stockMovementServiceValidationException) {
            return new JsonApiErrorResponse(new JsonApiErrors([
                $stockMovementServiceValidationException->serializeToJsonApiError(),
            ]));
        }

        return new Response('', Response::HTTP_OK);
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
