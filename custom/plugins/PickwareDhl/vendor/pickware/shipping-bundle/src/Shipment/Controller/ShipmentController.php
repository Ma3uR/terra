<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment\Controller;

use Pickware\ShippingBundle\Shipment\ShipmentBlueprint;
use Pickware\ShippingBundle\Shipment\ShipmentService;
use Pickware\HttpUtils\ResponseFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShipmentController
{
    /**
     * @var ShipmentService
     */
    private $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-shipment-blueprint-for-order",
     *     name="api.action.pickware-shipping.shipment.create-shipment-blueprint-for-order",
     *     methods={"POST"},
     * )
     */
    public function createShipmentBlueprintForOrder(Request $request, Context $context): Response
    {
        $orderId = $request->request->getAlnum('orderId');
        if (!$orderId) {
            return ResponseFactory::createParameterMissingResponse('orderId');
        }
        $shipmentBlueprint = $this->shipmentService->createShipmentBlueprintForOrder($orderId, $context);

        return new JsonResponse($shipmentBlueprint);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/create-shipment-for-order",
     *     name="api.action.pickware-shipping.shipment.create-shipment-for-order",
     *     methods={"POST"},
     * )
     */
    public function createShipmentForOrder(Request $request, Context $context): Response
    {
        $orderId = $request->request->getAlnum('orderId');
        if (!$orderId) {
            return ResponseFactory::createParameterMissingResponse('orderId');
        }

        $shipmentBlueprintArray = $request->get('shipmentBlueprint');
        if (!$shipmentBlueprintArray) {
            return ResponseFactory::createParameterMissingResponse('shipmentBlueprint');
        }

        $shipmentBlueprint = ShipmentBlueprint::fromArray($shipmentBlueprintArray);
        $result = $this->shipmentService->createShipmentForOrder($shipmentBlueprint, $orderId, $context);

        return new JsonResponse($result);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/pickware-shipping-shipment/{shipmentId}/aggregated-tracking-urls",
     *     name="api.pickware_shipping_shipment.aggregated-tracking-urls",
     *     methods={"GET"},
     *     requirements={"shipmentId"="[a-fA-F0-9]{32}"}
     * )
     */
    public function shipmentAggregatedTrackingUrls(string $shipmentId, Context $context): JsonResponse
    {
        $urls = $this->shipmentService->getTrackingUrlsForShipment($shipmentId, $context);

        return new JsonResponse($urls);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/shipment/cancel-shipment",
     *     name="api.action.pickware-shipping-shipment.cancel",
     *     methods={"POST"},
     * )
     */
    public function cancelShipment(Request $request, Context $context): JsonResponse
    {
        $shipmentId = $request->request->getAlnum('shipmentId');
        if (!$shipmentId) {
            return ResponseFactory::createParameterMissingResponse('shipmentId');
        }
        $result = $this->shipmentService->cancelShipment($shipmentId, $context);

        return new JsonResponse($result);
    }
}
