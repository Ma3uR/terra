<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Mail\Controller;

use Pickware\ShippingBundle\Mail\LabelMailerService;
use Pickware\HttpUtils\ResponseFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MailController
{
    /**
     * @var LabelMailerService
     */
    private $mailService;

    public function __construct(LabelMailerService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-shipping/mail/send-return-labels-of-shipment-to-order-customer",
     *     name="api.action.pickware-shipping.mail.send-return-labels-of-shipment-to-order-customer",
     *     methods={"POST"}
     * )
     * @param Request $request
     * @param Context $context
     * @return Response
     */
    public function sendReturnLabelOfShipmentToOrderCustomer(Request $request, Context $context): Response
    {
        $shipmentId = $request->request->getAlnum('shipmentId');
        if (!$shipmentId) {
            return ResponseFactory::createParameterMissingResponse('shipmentId');
        }
        $orderId = $request->request->getAlnum('orderId');
        if (!$orderId) {
            return ResponseFactory::createParameterMissingResponse('orderId');
        }
        $this->mailService->sendReturnLabelsOfShipmentToOrderCustomer($shipmentId, $orderId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
