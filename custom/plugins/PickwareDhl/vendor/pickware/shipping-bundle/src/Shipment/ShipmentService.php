<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment;

use Exception;
use LogicException;
use Pickware\ShippingBundle\Carrier\Capabilities\MultiTrackingCapability;
use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistry;
use Pickware\ShippingBundle\Carrier\Model\CarrierDefinition;
use Pickware\ShippingBundle\Carrier\Model\CarrierEntity;
use Pickware\ShippingBundle\ParcelHydration\ParcelHydrator;
use Pickware\ShippingBundle\ParcelPacking\ParcelPacker;
use Pickware\DalBundle\EntityCollectionExtension;
use Pickware\DalBundle\EntityManager;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigDefinition;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigEntity;
use Pickware\ShippingBundle\Config\ConfigService;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Throwable;

class ShipmentService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var EntityManager
     */
    private $dal;

    /**
     * @var ParcelHydrator
     */
    private $parcelHydrator;

    /**
     * @var CarrierAdapterRegistry
     */
    private $carrierAdapterRegistry;

    /**
     * @var ParcelPacker
     */
    private $parcelPacker;

    public function __construct(
        ConfigService $configService,
        EntityManager $dal,
        ParcelHydrator $parcelHydrator,
        CarrierAdapterRegistry $carrierAdapterRegistry,
        ParcelPacker $parcelPacker
    ) {
        $this->configService = $configService;
        $this->dal = $dal;
        $this->parcelHydrator = $parcelHydrator;
        $this->carrierAdapterRegistry = $carrierAdapterRegistry;
        $this->parcelPacker = $parcelPacker;
    }

    /**
     * @param string $orderId
     * @param Context $context
     * @return ShipmentBlueprint
     */
    public function createShipmentBlueprintForOrder(string $orderId, Context $context): ShipmentBlueprint
    {
        /** @var OrderEntity $order */
        $order = $this->dal->findByPrimaryKey(OrderDefinition::class, $orderId, $context, [
            'orderCustomer',
            'salesChannel',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingOrderAddress.countryState',
        ]);
        // We use the order delivery with the highest shipping costs as Shopware creates additional order deliveries
        // with negative shipping costs when applying shipping costs vouchers and just using the first or last order
        // delivery without sorting first can result in the wrong order delivery to be used.
        $orderDelivery = PickwareOrderDeliveryCollection::createFrom($order->getDeliveries())
            ->getOrderDeliveryWithHighestShippingCosts();

        $commonConfig = $this->configService->getCommonShippingConfigForSalesChannel(
            $order->getSalesChannel()->getId()
        );

        $shipmentBlueprint = new ShipmentBlueprint();
        $shipmentBlueprint->setSenderAddress($commonConfig->getSenderAddress());
        $shippingOrderAddress = $orderDelivery->getShippingOrderAddress();
        $receiverAddress = Address::fromShopwareOrderAddress($shippingOrderAddress);
        $receiverAddress->setEmail($order->getOrderCustomer()->getEmail());
        $shipmentBlueprint->setReceiverAddress($receiverAddress);

        /** @var ShippingMethodConfigEntity $shippingMethodConfig */
        $shippingMethodConfig = $this->dal->findOneBy(ShippingMethodConfigDefinition::class, [
            'shippingMethodId' => $orderDelivery->getShippingMethodId(),
        ], $context, ['carrier']);

        if ($shippingMethodConfig && $shippingMethodConfig->getCarrier()->isInstalled()) {
            $shipmentBlueprint->setCarrierTechnicalName($shippingMethodConfig->getCarrier()->getTechnicalName());
            $shipmentBlueprint->setShipmentConfig($shippingMethodConfig->getShipmentConfig());
        }

        $parcel = $this->parcelHydrator->hydrateParcelFromOrder($order->getId(), $context);
        $commonConfig->assignCustomsInformation($parcel->getCustomsInformation());
        if ($shippingMethodConfig) {
            $parcels = $this->parcelPacker->repackParcel(
                $parcel,
                $shippingMethodConfig->getParcelPackingConfiguration()
            );
        } else {
            $parcels = [$parcel];
        }
        $shipmentBlueprint->setParcels($parcels);

        return $shipmentBlueprint;
    }
    /**
     * @param ShipmentBlueprint $shipmentBlueprint
     * @param string $orderId
     * @param Context $context
     * @return ShipmentsOperationResultSet
     */
    public function createShipmentForOrder(
        ShipmentBlueprint $shipmentBlueprint,
        string $orderId,
        Context $context
    ): ShipmentsOperationResultSet {
        /** @var CarrierEntity $carrier */
        $carrier = $this->dal->findByPrimaryKey(
            CarrierDefinition::class,
            $shipmentBlueprint->getCarrierTechnicalName(),
            $context
        );
        if (!$carrier->isInstalled()) {
            throw new Exception(sprintf('Carrier %s is not installed.', $carrier->getTechnicalName()));
        }
        $carrierAdapter = $this->carrierAdapterRegistry->getCarrierAdapterByTechnicalName($carrier->getTechnicalName());

        /** @var OrderEntity $order */
        $order = $this->dal->findByPrimaryKey(OrderDefinition::class, $orderId, $context, [
            'salesChannel',
            'deliveries',
        ]);

        $shipmentId = Uuid::randomHex();
        $shipmentPayload = [
            'id' => $shipmentId,
            'carrierTechnicalName' => $carrier->getTechnicalName(),
            'shipmentBlueprint' => $shipmentBlueprint,
            'salesChannelId' => $order->getSalesChannelId(),
            'orders' => [
                [
                    'id' => $order->getId(),
                ],
            ],
        ];
        $this->dal->create(ShipmentDefinition::class, [$shipmentPayload], $context);
        /** @var ShipmentEntity $shipment */
        $shipment = $this->dal->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context);

        $carrierConfig = $this->configService->getConfigForSalesChannel(
            $carrier->getConfigDomain(),
            $order->getSalesChannel()->getId()
        );

        try {
            $result = $carrierAdapter->registerShipments([$shipment->getId()], $carrierConfig, $context);
        } catch (Throwable $e) {
            $this->dal->delete(ShipmentDefinition::class, [$shipmentId], $context);

            throw $e;
        }

        if (!$result->didProcessAllShipments([$shipment->getId()])) {
            throw new LogicException(sprintf(
                'Implementation of method registerShipments for carrier adapter "%s" did not process every passed ' .
                'ShipmentEntity. Please make sure that the method returns a ShipmentsOperationResultSet that in ' .
                'total includes every passed ShipmentEntity at least once.',
                get_class($carrierAdapter)
            ));
        }

        if (!$result->isAnyOperationResultSuccessful()) {
            $this->dal->delete(ShipmentDefinition::class, [$shipmentId], $context);

            return $result;
        }

        $shipment = $this->dal->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context, ['trackingCodes']);

        $newTrackingCodes = $shipment->getTrackingCodes()->map(function (TrackingCodeEntity $trackingCode) {
            return $trackingCode->getTrackingCode();
        });
        // We use the order delivery with the highest shipping costs as Shopware creates additional order deliveries
        // with negative shipping costs when applying shipping costs vouchers and just using the first or last order
        // delivery without sorting first can result in the wrong order delivery to be used.
        $orderDelivery = PickwareOrderDeliveryCollection::createFrom($order->getDeliveries())
            ->getOrderDeliveryWithHighestShippingCosts();
        $oldTrackingCodes = $orderDelivery->getTrackingCodes();
        $trackingCodes = array_values(array_unique(array_merge($newTrackingCodes, $oldTrackingCodes)));

        $payload = [
            'id' => $orderDelivery->getId(),
            'trackingCodes' => $trackingCodes,
        ];
        $this->dal->update(OrderDeliveryDefinition::class, [$payload], $context);

        return $result;
    }

    /**
     * @param string $shipmentId
     * @param Context $context
     * @return string[]
     */
    public function getTrackingUrlsForShipment(string $shipmentId, Context $context): array
    {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->dal->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context, [
            'trackingCodes',
        ]);
        $trackingCodes = $shipment->getTrackingCodes();

        if ($trackingCodes->count() === 0) {
            return [];
        }
        if ($trackingCodes->count() === 1 && $trackingCodes->first()->getTrackingUrl() !== null) {
            return [$trackingCodes->first()->getTrackingUrl()];
        }
        $carrierAdapter = $this->carrierAdapterRegistry->getCarrierAdapterByTechnicalName(
            $shipment->getCarrierTechnicalName()
        );
        if ($carrierAdapter instanceof MultiTrackingCapability) {
            $trackingCodeIds = EntityCollectionExtension::getField($shipment->getTrackingCodes(), 'id');

            return [$carrierAdapter->generateTrackingUrlForTrackingCodes($trackingCodeIds, $context)];
        }

        return array_values(array_filter($trackingCodes->map(function (TrackingCodeEntity $trackingCodeEntity) {
            return $trackingCodeEntity->getTrackingUrl();
        })));
    }

    /**
     * @param string $shipmentId
     * @param Context $context
     * @return ShipmentsOperationResultSet
     * @throws Exception
     */
    public function cancelShipment(string $shipmentId, Context $context): ShipmentsOperationResultSet
    {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->dal->findByPrimaryKey(ShipmentDefinition::class, $shipmentId, $context, [
            'carrier',
            'salesChannel',
        ]);

        $cancellationCapability = $this->carrierAdapterRegistry->getCancellationCapability(
            $shipment->getCarrierTechnicalName()
        );

        $carrierConfiguration = $this->configService->getConfigForSalesChannel(
            $shipment->getCarrier()->getConfigDomain(),
            $shipment->getSalesChannelId()
        );

        $result = $cancellationCapability->cancelShipments([$shipment->getId()], $carrierConfiguration, $context);
        if (!$result->didProcessAllShipments([$shipment->getId()])) {
            throw new LogicException(sprintf(
                'Implementation of method cancelShipments for carrier "%s" did not process every passed ' .
                'shipment. Please make sure that the method returns a %s that in ' .
                'total includes every passed shipment at least once.',
                $shipment->getCarrier()->getTechnicalName(),
                ShipmentsOperationResultSet::class
            ));
        }

        $resultForShipment = $result->getResultForShipment($shipment->getId());
        if ($resultForShipment === ShipmentsOperationResultSet::RESULT_SUCCESSFUL) {
            $shipmentPayload = [
                'id' => $shipment->getId(),
                'cancelled' => true,
            ];
            $this->dal->update(ShipmentDefinition::class, [$shipmentPayload], $context);
        }

        return $result;
    }
}
