<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl;

use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeDefinition;
use Pickware\ShippingBundle\Shipment\ShipmentsOperationResult;
use Pickware\ShippingBundle\Shipment\ShipmentsOperationResultSet;
use Pickware\DalBundle\EntityManager;
use Pickware\DocumentBundle\DocumentContentsService;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Pickware\DocumentBundle\PageFormat;
use Pickware\ShippingBundle\PickwareShippingBundle;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use stdClass;

class DhlResponseProcessor
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DocumentContentsService
     */
    private $documentContentsService;

    public function __construct(EntityManager $entityManager, DocumentContentsService $documentContentsService)
    {
        $this->entityManager = $entityManager;
        $this->documentContentsService = $documentContentsService;
    }

    public function processCreateShipmentOrderResponse(
        stdClass $response,
        Context $context
    ): ShipmentsOperationResultSet {
        $shipmentsOperationResultSet = new ShipmentsOperationResultSet();

        $creationStates = is_array($response->CreationState) ? $response->CreationState : [$response->CreationState];
        foreach ($creationStates as $creationState) {
            $parcelDescriptor = ParcelReference::fromString($creationState->sequenceNumber);

            /** @var ShipmentEntity $shipment */
            $shipment = $this->entityManager->findByPrimaryKey(
                ShipmentDefinition::class,
                $parcelDescriptor->getShipmentId(),
                $context
            );
            if (!$shipment) {
                throw DhlAdapterException::shipmentNotFound($parcelDescriptor->getShipmentId());
            }

            $shipmentsOperationResult = $this->createShipmentsOperationResult(
                $creationState,
                $shipment,
                $parcelDescriptor->getIndex()
            );
            $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);
            if (!$shipmentsOperationResult->isSuccessful()) {
                continue;
            }

            $this->createLabelDocument($shipment, $creationState, $context);

            // Save return label document to database if one is contained in the request
            if (isset($creationState->LabelData->returnLabelData)) {
                $this->createReturnLabelDocument($shipment, $creationState, $context);
            }

            // Save export documents to database if one is contained in the request
            if (isset($creationState->LabelData->exportLabelData)) {
                $this->createExportLabelDocument($shipment, $creationState, $context);
            }
        }

        return $shipmentsOperationResultSet;
    }

    private function createShipmentsOperationResult(
        $creationState,
        ShipmentEntity $shipment,
        $parcelIndex
    ): ShipmentsOperationResult {
        $operationDescription = sprintf(
            'Create label to %s %s, parcel %d',
            $shipment->getShipmentBlueprint()->getReceiverAddress()->getFirstName(),
            $shipment->getShipmentBlueprint()->getReceiverAddress()->getLastName(),
            $parcelIndex + 1
        );

        if ($creationState->LabelData->Status->statusCode !== 0) {
            $errorMessages = array_values(array_unique(array_merge(
                [$creationState->LabelData->Status->statusText],
                $creationState->LabelData->Status->statusMessage
            )));

            return ShipmentsOperationResult::createFailedOperationResult(
                [$shipment->getId()],
                $operationDescription,
                $errorMessages
            );
        }

        return ShipmentsOperationResult::createSuccessfulOperationResult(
            [$shipment->getId()],
            $operationDescription
        );
    }

    private function createLabelDocument(ShipmentEntity $shipment, stdClass $shipmentData, Context $context): void
    {
        $documentId = $this->documentContentsService->saveStringAsDocument(
            base64_decode($shipmentData->LabelData->labelData),
            $context,
            [
                'mimeType' => 'application/pdf',
                'orientation' => DocumentEntity::ORIENTATION_PORTRAIT,
                'documentTypeTechnicalName' => PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_SHIPPING_LABEL,
                'pageFormat' => PageFormat::createDinPageFormat('A5'),
                'extensions' => [
                    'pickwareShippingShipments' => [
                        [
                            'id' => $shipment->getId(),
                        ],
                    ],
                ],
            ]
        );

        $shipmentNumber = $shipmentData->shipmentNumber;
        $trackingCodesPayload = [
            'id' => Uuid::randomHex(),
            'trackingCode' => $shipmentNumber,
            'trackingUrl' => DhlAdapter::getTrackingUrlForShipmentNumbers([$shipmentNumber]),
            'metaInformation' => [
                'type' => DhlAdapter::TRACKING_CODE_TYPE_SHIPMENT_NUMBER,
            ],
            'shipmentId' => $shipment->getId(),
            'documents' => [
                [
                    'id' => $documentId,
                ],
            ],
        ];
        $this->entityManager->create(TrackingCodeDefinition::class, [$trackingCodesPayload], $context);
    }

    private function createReturnLabelDocument(ShipmentEntity $shipment, stdClass $shipmentData, Context $context): void
    {
        $returnDocumentId = $this->documentContentsService->saveStringAsDocument(
            base64_decode($shipmentData->LabelData->returnLabelData),
            $context,
            [
                'mimeType' => 'application/pdf',
                'orientation' => DocumentEntity::ORIENTATION_PORTRAIT,
                'documentTypeTechnicalName' => PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_RETURN_LABEL,
                'pageFormat' => PageFormat::createDinPageFormat('A5'),
                'extensions' => [
                    'pickwareShippingShipments' => [
                        [
                            'id' => $shipment->getId(),
                        ],
                    ],
                ],
            ]
        );

        if (isset($shipmentData->returnShipmentNumber)) {
            $returnShipmentNumber = $shipmentData->returnShipmentNumber;
            $trackingCodePayload = [
                'id' => Uuid::randomHex(),
                'trackingCode' => $returnShipmentNumber,
                'trackingUrl' => DhlAdapter::getTrackingUrlForShipmentNumbers([$returnShipmentNumber]),
                'metaInformation' => [
                    'type' => DhlAdapter::TRACKING_CODE_TYPE_RETURN_SHIPMENT_NUMBER,
                ],
                'shipmentId' => $shipment->getId(),
                'documents' => [
                    [
                        'id' => $returnDocumentId,
                    ],
                ],
            ];
            $this->entityManager->create(TrackingCodeDefinition::class, [$trackingCodePayload], $context);
        }
    }

    private function createExportLabelDocument(ShipmentEntity $shipment, stdClass $shipmentData, Context $context): void
    {
        $this->documentContentsService->saveStringAsDocument(
            base64_decode($shipmentData->LabelData->exportLabelData),
            $context,
            [
                'mimeType' => 'application/pdf',
                'orientation' => DocumentEntity::ORIENTATION_PORTRAIT,
                'documentTypeTechnicalName' => PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_CUSTOMS_DECLARATION_CN23,
                'pageFormat' => PageFormat::createDinPageFormat('A5'),
                'extensions' => [
                    'pickwareShippingShipments' => [
                        [
                            'id' => $shipment->getId(),
                        ],
                    ],
                ],
            ]
        );
    }
}
