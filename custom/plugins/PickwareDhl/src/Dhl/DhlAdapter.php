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

use Pickware\ShippingBundle\Carrier\AbstractCarrierAdapter;
use Pickware\ShippingBundle\Carrier\Capabilities\CancellationCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\MultiTrackingCapability;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlApiClientFactory;
use Pickware\PickwareDhl\Dhl\ApiClient\Requests\CreateShipmentOrderRequest;
use Pickware\ShippingBundle\Config\Config;
use Pickware\DalBundle\EntityCollectionExtension;
use Pickware\DalBundle\EntityManager;
use Pickware\PickwareDhl\Dhl\ApiClient\Requests\DeleteShipmentOrderRequest;
use Pickware\ShippingBundle\Shipment\Model\ShipmentCollection;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeDefinition;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeEntity;
use Pickware\ShippingBundle\Shipment\ShipmentsOperationResult;
use Pickware\ShippingBundle\Shipment\ShipmentsOperationResultSet;
use Shopware\Core\Framework\Context;

class DhlAdapter extends AbstractCarrierAdapter implements MultiTrackingCapability, CancellationCapability
{
    public const TRACKING_CODE_TYPE_SHIPMENT_NUMBER = 'shipmentNumber';
    public const TRACKING_CODE_TYPE_RETURN_SHIPMENT_NUMBER = 'returnShipmentNumber';

    /**
     * @var DhlShipmentOrderFactory
     */
    private $shipmentOrderFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DhlApiClientFactory
     */
    private $dhlApiClientFactory;

    /**
     * @var DhlResponseProcessor
     */
    private $dhlResponseProcessor;

    public function __construct(
        DhlShipmentOrderFactory $shipmentOrderFactory,
        EntityManager $entityManager,
        DhlApiClientFactory $dhlApiClientFactory,
        DhlResponseProcessor $dhlResponseProcessor
    ) {
        $this->shipmentOrderFactory = $shipmentOrderFactory;
        $this->entityManager = $entityManager;
        $this->dhlApiClientFactory = $dhlApiClientFactory;
        $this->dhlResponseProcessor = $dhlResponseProcessor;
    }

    /**
     * @param string[] $shipmentNumbers
     * @return string
     */
    public static function getTrackingUrlForShipmentNumbers(array $shipmentNumbers): string
    {
        return sprintf(
            'https://www.dhl.de/de/privatkunden/dhl-sendungsverfolgung.html?piececode=%s',
            implode(',', $shipmentNumbers)
        );
    }

    public function registerShipments(
        array $shipmentIds,
        Config $carrierConfig,
        Context $context
    ): ShipmentsOperationResultSet {
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(ShipmentDefinition::class, ['id' => $shipmentIds], $context);
        $dhlConfig = new DhlConfig($carrierConfig);
        $dhlApiClientConfig = $dhlConfig->getDhlApiClientConfig();
        $dhlApiClient = $this->dhlApiClientFactory->createDhlApiClient($dhlApiClientConfig);

        $shipmentOrders = $this->getShipmentOrdersForShipments($shipments, $dhlConfig, $context);
        $response = $dhlApiClient->sendRequest(new CreateShipmentOrderRequest($shipmentOrders));

        if ($response->Status->statusCode !== 0 && !isset($response->CreationState)) {
            throw new DhlAdapterException($response->Status->statusText);
        }

        return $this->dhlResponseProcessor->processCreateShipmentOrderResponse($response, $context);
    }

    private function getShipmentOrdersForShipments(
        ShipmentCollection $shipments,
        DhlConfig $dhlConfig,
        Context $context
    ): array {
        $shipmentOrders = $shipments->map(
            function (ShipmentEntity $shipment) use ($context, $dhlConfig) {
                return $this->shipmentOrderFactory->createShipmentOrdersForShipment(
                    $shipment->getId(),
                    $dhlConfig,
                    $context
                );
            }
        );

        return array_merge(...array_values($shipmentOrders));
    }

    public function generateTrackingUrlForTrackingCodes(array $trackingCodeIds, Context $context): string
    {
        $trackingCodes = $this->entityManager->findBy(
            TrackingCodeDefinition::class,
            ['id' => $trackingCodeIds],
            $context
        );
        $shipmentNumbers = EntityCollectionExtension::getField($trackingCodes, 'trackingCode');

        return self::getTrackingUrlForShipmentNumbers($shipmentNumbers);
    }

    public function cancelShipments(
        array $shipmentIds,
        Config $carrierConfig,
        Context $context
    ): ShipmentsOperationResultSet {
        /** @var ShipmentCollection $shipments */
        $shipments = $this->entityManager->findBy(
            ShipmentDefinition::class,
            ['id' => $shipmentIds],
            $context,
            ['trackingCodes']
        );

        $shipmentsOperationResultSet = new ShipmentsOperationResultSet();

        $shipmentNumbers = [];
        $shipmentNumbersShipmentsMapping = [];
        foreach ($shipments as $shipment) {
            $numberOfCancellableTrackingCodesForShipment = 0;
            foreach ($shipment->getTrackingCodes() as $trackingCode) {
                $metaInformation = $trackingCode->getMetaInformation();
                if ($metaInformation['type'] !== self::TRACKING_CODE_TYPE_SHIPMENT_NUMBER) {
                    continue;
                }
                if (isset($metaInformation['cancelled']) && $metaInformation['cancelled']) {
                    $operationDescription = sprintf('Cancel label %s', $trackingCode);
                    $shipmentsOperationResult = ShipmentsOperationResult::createSuccessfulOperationResult(
                        EntityCollectionExtension::getField($shipments, 'id'),
                        $operationDescription
                    );
                    $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);

                    continue;
                }
                $shipmentNumbers[] = $trackingCode->getTrackingCode();
                $shipmentNumbersShipmentsMapping[$trackingCode->getTrackingCode()][] = $shipment->getId();
                $numberOfCancellableTrackingCodesForShipment ++;
            }

            if ($numberOfCancellableTrackingCodesForShipment === 0) {
                $operationDescription = sprintf('Cancel shipment %s', $shipment->getId());
                $shipmentsOperationResult = ShipmentsOperationResult::createFailedOperationResult(
                    EntityCollectionExtension::getField($shipments, 'id'),
                    $operationDescription,
                    [
                        'This shipment has no tracking codes that can be used to cancel the shipment',
                    ]
                );
                $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);
            }
        }

        if (count($shipmentNumbers) === 0) {
            return $shipmentsOperationResultSet;
        }

        $dhlConfig = new DhlConfig($carrierConfig);
        $dhlApiConfig = $dhlConfig->getDhlApiClientConfig();
        $dhlApiClient = $this->dhlApiClientFactory->createDhlApiClient($dhlApiConfig);

        $result = $dhlApiClient->sendRequest(new DeleteShipmentOrderRequest($shipmentNumbers));

        $deletionStates = is_array($result->DeletionState) ? $result->DeletionState : [$result->DeletionState];
        $trackingCodePayload = [];
        foreach ($deletionStates as $deletionState) {
            $shipmentNumber = $deletionState->shipmentNumber;
            $operationDescription = sprintf('Cancel label %s', $shipmentNumber);

            $affectedShipmentIds = $shipmentNumbersShipmentsMapping[$shipmentNumber];
            $affectedShipments = $shipments->filter(function (ShipmentEntity $shipment) use ($affectedShipmentIds) {
                return in_array($shipment->getId(), $affectedShipmentIds, true);
            });

            if ($deletionState->Status->statusCode === 0) {
                $shipmentsOperationResult = ShipmentsOperationResult::createSuccessfulOperationResult(
                    EntityCollectionExtension::getField($affectedShipments, 'id'),
                    $operationDescription
                );

                // Mark the tracking codes as cancelled
                foreach ($affectedShipments as $affectedShipment) {
                    $affectedTrackingCodes = $affectedShipment->getTrackingCodes()->filter(function (TrackingCodeEntity $trackingCode) use ($shipmentNumber) {
                        return $trackingCode->getTrackingCode() === $shipmentNumber;
                    });

                    foreach ($affectedTrackingCodes as $affectedTrackingCode) {
                        $metaInformation = $affectedTrackingCode->getMetaInformation();
                        $metaInformation['cancelled'] = true;
                        $trackingCodePayload[] = [
                            'id' => $affectedTrackingCode->getId(),
                            'metaInformation' => $metaInformation,
                        ];
                    }
                }
            } else {
                $shipmentsOperationResult = ShipmentsOperationResult::createFailedOperationResult(
                    EntityCollectionExtension::getField($affectedShipments, 'id'),
                    $operationDescription,
                    [$deletionState->Status->statusText]
                );
            }

            $shipmentsOperationResultSet->addShipmentOperationResult($shipmentsOperationResult);
        }

        if (count($trackingCodePayload) !== 0) {
            $this->entityManager->upsert(TrackingCodeDefinition::class, $trackingCodePayload, $context);
        }

        return $shipmentsOperationResultSet;
    }
}
