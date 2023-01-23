<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Mail;

use League\Flysystem\FilesystemInterface;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\DalBundle\ContextFactory;
use Pickware\DalBundle\EntityManager;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Pickware\ShippingBundle\PickwareShippingBundle;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Content\Mail\Service\MailService as ShopwareMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class LabelMailerService
{
    private const SHOP_NAME_CONFIG_KEY = 'core.basicInformation.shopName';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ShopwareMailService
     */
    private $shopwareMailService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @var FilesystemInterface
     */
    private $documentBundleFileSystem;

    public function __construct(
        EntityManager $entityManager,
        ShopwareMailService $shopwareMailService,
        SystemConfigService $systemConfigService,
        ContextFactory $contextFactory,
        FilesystemInterface $documentBundleFileSystem
    ) {
        $this->entityManager = $entityManager;
        $this->shopwareMailService = $shopwareMailService;
        $this->systemConfigService = $systemConfigService;
        $this->contextFactory = $contextFactory;
        $this->documentBundleFileSystem = $documentBundleFileSystem;
    }

    public function sendReturnLabelsOfShipmentToOrderCustomer(
        string $shipmentId,
        string $orderId,
        Context $context
    ): void {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->entityManager->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context, [
            'documents',
        ]);
        if (!$shipment) {
            throw LabelMailerException::shipmentNotFound($shipmentId);
        }

        $returnLabelDocuments = $shipment->getDocuments()->filterByProperty(
            'documentTypeTechnicalName',
            PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_RETURN_LABEL
        );
        if ($returnLabelDocuments->count() === 0) {
            throw LabelMailerException::shipmentHasNoReturnLabelDocuments($shipmentId);
        }

        // Retrieve mail template and order in correct language
        $orderContext = $this->contextFactory->deriveOrderContext($orderId, $context);
        $orderContext->setConsiderInheritance(true);

        $returnLabelMailTemplates = $this->entityManager->findBy(MailTemplateDefinition::class, [
            'mailTemplateType.pickwareShippingCarriersReturnLabel.technicalName' => $shipment->getCarrierTechnicalName(),
        ], $orderContext);
        $returnLabelMailTemplate = $returnLabelMailTemplates->first();
        if (!$returnLabelMailTemplate) {
            throw LabelMailerException::noMailTemplateConfiguredForCarrier(
                $shipment->getCarrierTechnicalName()
            );
        }

        /** @var OrderEntity $order */
        $order = $this->entityManager->findByPrimaryKey(OrderDefinition::class, $orderId, $orderContext, [
            'orderCustomer.salutation'
        ]);
        $orderCustomer = $order->getOrderCustomer();
        $salesChannelId = $order->getSalesChannelId();
        $shopName = $this->systemConfigService->get(self::SHOP_NAME_CONFIG_KEY, $salesChannelId);

        $message = $this->shopwareMailService->send([
            'salesChannelId' => $salesChannelId,
            'recipients' => [
                $orderCustomer->getEmail() => sprintf(
                    '%s %s',
                    $orderCustomer->getFirstName(),
                    $orderCustomer->getLastName()
                ),
            ],
            'contentHtml' => $returnLabelMailTemplate->getContentHtml(),
            'contentPlain' => $returnLabelMailTemplate->getContentPlain(),
            'subject' => $returnLabelMailTemplate->getSubject(),
            'senderName' => $returnLabelMailTemplate->getSenderName(),
            'binAttachments' => array_values($returnLabelDocuments->map(
                function (DocumentEntity $document) use ($order) {
                    return [
                        'content' => $this->documentBundleFileSystem->read($document->getPathInPrivateFileSystem()),
                        'fileName' => sprintf('return-label-%s.pdf', $order->getOrderNumber()),
                        'mimeType' => $document->getMimeType(),
                    ];
                }
            )),
        ], $context, [
            'order' => $order,
            'shopName' => $shopName,
            'returnLabelDocuments' => $returnLabelDocuments,
        ]);
        if ($message === null) {
            throw LabelMailerException::failedToRenderMailTemplate($returnLabelMailTemplate->getId(), $order->getId());
        }
    }
}
