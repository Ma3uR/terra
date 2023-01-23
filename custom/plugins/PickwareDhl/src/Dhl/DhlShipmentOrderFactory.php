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

use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\DispatchNotificationOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\ShipmentOrder;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentEntity;
use Pickware\DalBundle\EntityManager;
use Pickware\MoneyBundle\Currency;
use Pickware\MoneyBundle\CurrencyConverter;
use Pickware\MoneyBundle\CurrencyConverterException;
use Shopware\Core\Framework\Context;

class DhlShipmentOrderFactory
{
    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(CurrencyConverter $currencyConverter, EntityManager $entityManager)
    {
        $this->currencyConverter = $currencyConverter;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $shipmentId
     * @param DhlConfig $dhlConfig
     * @param Context $context
     * @return ShipmentOrder[]
     */
    public function createShipmentOrdersForShipment(string $shipmentId, DhlConfig $dhlConfig, Context $context): array
    {
        /** @var ShipmentEntity $shipment */
        $shipment = $this->entityManager->findByPrimaryKey(
            ShipmentDefinition::class,
            $shipmentId,
            $context
        );
        if (!$shipment) {
            throw DhlAdapterException::shipmentNotFound($shipmentId);
        }
        $shipmentBlueprint = $shipment->getShipmentBlueprint();
        $receiverAddress = $shipmentBlueprint->getReceiverAddress();
        if (!$dhlConfig->isEmailTransferAllowed()) {
            $receiverAddress = $receiverAddress->copyWithoutEmail();
        }
        if (!$dhlConfig->isPhoneTransferAllowed()) {
            $receiverAddress = $receiverAddress->copyWithoutPhone();
        }

        $dhlShipmentConfig = new DhlShipmentConfig($shipmentBlueprint->getShipmentConfig());
        $product = $dhlShipmentConfig->getProduct();
        $shipmentOrderOptions = $dhlShipmentConfig->getShipmentOrderOptions($dhlConfig);
        if ($dhlConfig->isDispatchNotificationEnabled() && $receiverAddress->getEmail()) {
            $shipmentOrderOptions[] = new DispatchNotificationOption($receiverAddress->getEmail());
        }

        if (count($shipmentBlueprint->getParcels()) === 0) {
            throw DhlAdapterException::shipmentBlueprintHasNoParcels();
        }

        $shipmentOrders = [];
        foreach ($shipmentBlueprint->getParcels() as $parcelIndex => $parcel) {
            $shipmentOrder = new ShipmentOrder($dhlConfig->getBillingInformation());
            $shipmentOrder->setReceiverAddress($receiverAddress);
            $shipmentOrder->setSenderAddress($shipmentBlueprint->getSenderAddress());
            $shipmentOrder->setProduct($product);
            $shipmentOrder->setParcel($parcel);
            $shipmentOrder->setShipmentOrderOptions($shipmentOrderOptions);
            $shipmentOrder->setSequenceNumber(
                (new ParcelReference($shipmentId, $parcelIndex))->toString()
            );

            $termsOfTrade = $dhlShipmentConfig->getTermsOfTrade();
            if ($termsOfTrade !== null) {
                $shipmentOrder->enableExportDocumentCreation($termsOfTrade);
                try {
                    $parcel->convertAllMoneyValuesToSameCurrency($this->currencyConverter, new Currency('EUR'));
                } catch (CurrencyConverterException $e) {
                    throw DhlAdapterException::customsValuesCouldNotBeConvertedToEuro($e);
                }
            }
            $shipmentOrders[] = $shipmentOrder;
        }

        return $shipmentOrders;
    }
}
