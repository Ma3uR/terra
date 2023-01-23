<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelHydration;

use Pickware\DalBundle\ContextFactory;
use Pickware\DalBundle\EntityManager;
use Pickware\MoneyBundle\Currency;
use Pickware\UnitsOfMeasurement\Dimensions\BoxDimensions;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Length;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;
use Pickware\MoneyBundle\MoneyValue;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelCustomsInformation;
use Pickware\ShippingBundle\Parcel\ParcelItem;
use Pickware\ShippingBundle\Parcel\ParcelItemCustomsInformation;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Document\DocumentEntity as ShopwareDocumentEntity;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class ParcelHydrator
{
    public const CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_DESCRIPTION = 'pickware_shipping_customs_information_description';
    public const CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_TARIFF_NUMBER = 'pickware_shipping_customs_information_tariff_number';
    public const CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_COUNTRY_OF_ORIGIN = 'pickware_shipping_customs_information_country_of_origin';

    private const SUPPORTED_ORDER_LINE_ITEM_TYPES = [
        LineItem::PRODUCT_LINE_ITEM_TYPE,
        LineItem::CUSTOM_LINE_ITEM_TYPE,
    ];

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager,
        ContextFactory $contextFactory
    ) {
        $this->entityManager = $entityManager;
        $this->contextFactory = $contextFactory;
    }

    public function hydrateParcelFromOrder(string $orderId, Context $context): Parcel
    {
        // Consider inheritance when fetching products for inherited fields (e.g. name, weight)
        $orderContext = $this->contextFactory->deriveOrderContext($orderId, $context);
        $orderContext->setConsiderInheritance(true);
        /** @var OrderEntity $order */
        $order = $this->entityManager->findByPrimaryKey(
            OrderDefinition::class,
            $orderId,
            $orderContext,
            [
                'currency',
                'documents.documentType',
                'lineItems.product',
            ]
        );

        $parcel = new Parcel();
        $parcel->setCustomerReference($order->getOrderNumber());

        $customsInformation = new ParcelCustomsInformation($parcel);
        $currencyCode = $order->getCurrency()->getIsoCode();
        $shippingCosts = new MoneyValue($order->getShippingTotal(), new Currency($currencyCode));
        $customsInformation->addFee(ParcelCustomsInformation::FEE_TYPE_SHIPPING_COSTS, $shippingCosts);

        $invoices = $order->getDocuments()->filter(
            function (ShopwareDocumentEntity $document) {
                return $document->getDocumentType()->getTechnicalName() === InvoiceGenerator::INVOICE;
            }
        );
        $invoiceNumbers = $invoices->map(function (ShopwareDocumentEntity $document) {
            return $document->getConfig()['documentNumber'];
        });
        $customsInformation->setInvoiceNumbers(array_values($invoiceNumbers));

        /** @var OrderLineItemEntity $orderLineItem */
        foreach ($order->getLineItems() as $orderLineItem) {
            if (!in_array($orderLineItem->getType(), self::SUPPORTED_ORDER_LINE_ITEM_TYPES, true)) {
                continue;
            }

            $parcelItem = new ParcelItem($orderLineItem->getQuantity());
            $parcel->addItem($parcelItem);

            $itemCustomsInformation = new ParcelItemCustomsInformation($parcelItem);
            $customsValue = new MoneyValue(
                $orderLineItem->getPrice()->getUnitPrice(),
                new Currency($order->getCurrency()->getIsoCode())
            );
            $itemCustomsInformation->setCustomsValue($customsValue);

            $product = $orderLineItem->getProduct();
            if (!$product) {
                // If the product does not exist (i.e. has been deleted) only use the label of the order line item
                $parcelItem->setName($orderLineItem->getLabel());
                $itemCustomsInformation->setDescription($orderLineItem->getLabel());

                continue;
            }

            $productName = $product->getName() ?: $product->getTranslation('name');
            $parcelItem->setName($productName);
            $parcelItem->setUnitWeight($product->getWeight() ? new Weight($product->getWeight(), 'kg') : null);
            if ($product->getWidth() !== null && $product->getHeight() !== null && $product->getLength() !== null) {
                $parcelItem->setUnitDimensions(new BoxDimensions(
                    new Length($product->getWidth(), 'mm'),
                    new Length($product->getHeight(), 'mm'),
                    new Length($product->getLength(), 'mm')
                ));
            }

            $customFields = $product->getCustomFields();

            $description = $customFields[self::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_DESCRIPTION] ?? '';
            if (!$description) {
                // If no explicit description for this product was provided, use the product name as fallback
                $description = $productName;
            }

            $itemCustomsInformation->setDescription($description);
            $itemCustomsInformation->setTariffNumber(
                $customFields[self::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_TARIFF_NUMBER] ?? null
            );
            $itemCustomsInformation->setCountryIsoOfOrigin(
                $customFields[self::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_COUNTRY_OF_ORIGIN] ?? null
            );
        }

        return $parcel;
    }
}
