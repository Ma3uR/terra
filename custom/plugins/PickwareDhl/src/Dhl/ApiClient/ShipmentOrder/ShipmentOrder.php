<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlApiClientException;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlBillingInformation;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlProduct;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\AbstractShipmentOrderOption;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelCustomsInformation;
use Pickware\ShippingBundle\Shipment\Address;
use Pickware\MoneyBundle\Currency;

class ShipmentOrder
{
    public const FRANKATUR_CODES = [
        'DDP',
        'DXV',
        'DDX',
        'DDU',
    ];

    public const INCOTERM_CODES = [
        'EXW',
        'FCA',
        'CPT',
        'CIP',
        'DAT',
        'DAP',
        'DDP',
    ];

    public const EXPORT_TYPE_MAPPING = [
        ParcelCustomsInformation::SHIPMENT_TYPE_COMMERCIAL_SAMPLE => 'COMMERCIAL_SAMPLE',
        ParcelCustomsInformation::SHIPMENT_TYPE_DOCUMENTS => 'DOCUMENT',
        ParcelCustomsInformation::SHIPMENT_TYPE_GIFT => 'PRESENT',
        ParcelCustomsInformation::SHIPMENT_TYPE_OTHER => 'OTHER',
        ParcelCustomsInformation::SHIPMENT_TYPE_RETURNED_GOODS => 'RETURN_OF_GOODS',
        ParcelCustomsInformation::SHIPMENT_TYPE_SALE_OF_GOODS => 'COMMERCIAL_GOODS',
    ];

    /**
     * @var DhlBillingInformation
     */
    private $billingInformation;

    /**
     * @var Address
     */
    private $receiverAddress;

    /**
     * @var Address
     */
    private $senderAddress;

    /**
     * @var Parcel
     */
    private $parcel;

    /**
     * @var DhlProduct
     */
    private $product;

    /**
     * @var bool
     */
    private $exportDocumentCreationEnabled = false;

    /**
     * @var string
     */
    private $termsOfTrade = self::INCOTERM_CODES[0];

    /**
     * @var DateTime
     */
    private $shipmentDate;

    /**
     * @var AbstractShipmentOrderOption[]
     */
    private $shipmentOrderOptions = [];

    /**
     * @var string
     */
    private $sequenceNumber;

    public function __construct(DhlBillingInformation $billingInformation)
    {
        $this->billingInformation = $billingInformation;
        // DHL expects the shipment date based on the timezone of Germany.
        $this->shipmentDate = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    }

    public function toArray(): array
    {
        $parcelWeight = $this->parcel->getTotalWeight();
        if ($parcelWeight === null) {
            throw DhlApiClientException::parcelHasItemsWithUndefinedWeight();
        }
        $shipmentDetails = [
            'shipmentDate' => $this->shipmentDate->format('Y-m-d'),
            'product' => $this->product->getCode(),
            'accountNumber' => $this->billingInformation->getBillingNumberForProduct($this->product),
            'ShipmentItem' => [
                'weightInKG' => $parcelWeight->convertTo('kg'),
            ],
            'Service' => [],
        ];

        if ($this->parcel->getCustomerReference() !== null) {
            $shipmentDetails['customerReference'] = $this->parcel->getCustomerReference();
        }

        if ($this->parcel->getDimensions() !== null) {
            // Add the parcel dimensions
            $shipmentDetails['ShipmentItem']['lengthInCM'] = ceil(
                $this->parcel->getDimensions()->getLength()->convertTo('cm')
            );
            $shipmentDetails['ShipmentItem']['widthInCM'] = ceil(
                $this->parcel->getDimensions()->getWidth()->convertTo('cm')
            );
            $shipmentDetails['ShipmentItem']['heightInCM'] = ceil(
                $this->parcel->getDimensions()->getHeight()->convertTo('cm')
            );
        }

        self::validateAddress('sender', $this->senderAddress);
        self::validateAddress('receiver', $this->receiverAddress);

        $shipment = [
            'ShipmentDetails' => $shipmentDetails,
            'Shipper' => self::getAddressAsShipperAddressArray($this->senderAddress),
            'Receiver' => self::getAddressAsReceiverAddressArray($this->receiverAddress),
        ];
        if ($this->exportDocumentCreationEnabled) {
            $shipment['ExportDocument'] = $this->createExportDocumentArray();
        }

        $shipmentOrder = [
            'sequenceNumber' => $this->sequenceNumber,
            'Shipment' => $shipment,
        ];

        foreach ($this->shipmentOrderOptions as $shipmentOrderOption) {
            $shipmentOrderOption->applyToShipmentOrderArray($shipmentOrder);
        }

        return $shipmentOrder;
    }

    private static function getAddressAsShipperAddressArray(Address $address): array
    {
        return [
            'Name' => $address->getOptimizedNameArray(['name1', 'name2', 'name3']),
            'Address' => [
                'streetName' => $address->getStreet(),
                'streetNumber' => $address->getHouseNumber(),
                'zip' => $address->getZipCode(),
                'city' => $address->getCity(),
                'Origin' => [
                    'countryISOCode' => $address->getCountryIso(),
                    'state' => $address->getStateIso(),
                ],
            ],
            'Communication' => [
                'contactPerson' => sprintf('%s %s', $address->getFirstName(), $address->getLastName()),
                'phone' => $address->getPhone(),
                'email' => $address->getEmail(),
            ],
        ];
    }

    private static function getAddressAsReceiverAddressArray(Address $address): array
    {
        $nameArray = $address->getOptimizedNameArray(['name1', 'name2', 'name3']);

        $addressArray = [
            'name2' => $nameArray['name2'] ?? null,
            'name3' => $nameArray['name3'] ?? null,
            'streetName' => $address->getStreet(),
            'streetNumber' => $address->getHouseNumber(),
            'zip' => $address->getZipCode(),
            'city' => $address->getCity(),
            'Origin' => [
                'countryISOCode' => $address->getCountryIso(),
                'state' => $address->getStateIso(),
            ],
        ];

        return [
            'name1' => $nameArray['name1'] ?? null,
            'Communication' => [
                'contactPerson' => sprintf('%s %s', $address->getFirstName(), $address->getLastName()),
                'phone' => $address->getPhone(),
                'email' => $address->getEmail(),
            ],
            'Address' => $addressArray,
        ];
    }

    /**
     * @param string $addressOwner The owner of the address (i.e. 'sender', 'receiver')
     * @param Address $address
     * @throws DhlApiClientException
     */
    private static function validateAddress(string $addressOwner, Address $address): void
    {
        if (count($address->getOptimizedNameArray()) === 0) {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'name, company or address addition');
        }
        if ($address->getStreet() === '') {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'street name');
        }
        if ($address->getHouseNumber() === '') {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'house number');
        }
        if ($address->getZipCode() === '') {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'zip code');
        }
        if ($address->getCity() === '') {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'city');
        }
        if ($address->getCountryIso() === '') {
            throw DhlApiClientException::missingAddressProperty($addressOwner, 'country');
        }
    }

    public function setReceiverAddress(Address $receiverAddress): void
    {
        $this->receiverAddress = $receiverAddress;
    }

    public function setSenderAddress(Address $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    public function getParcel(): Parcel
    {
        return $this->parcel;
    }

    public function setParcel(Parcel $parcel): void
    {
        $this->parcel = $parcel;
    }

    public function setProduct(DhlProduct $product): void
    {
        $this->product = $product;
    }

    /**
     * @return AbstractShipmentOrderOption[]
     */
    public function getShipmentOrderOptions(): array
    {
        return $this->shipmentOrderOptions;
    }

    /**
     * @param AbstractShipmentOrderOption[] $shipmentOrderOptions
     */
    public function setShipmentOrderOptions(array $shipmentOrderOptions): void
    {
        $this->shipmentOrderOptions = $shipmentOrderOptions;
    }

    public function enableExportDocumentCreation(string $termsOfTrade): void
    {
        if (!in_array($termsOfTrade, self::FRANKATUR_CODES, true)
            && !in_array($termsOfTrade, self::INCOTERM_CODES, true)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Passed terms of trade code "%s" is not a valid incoterm or frankatur code.',
                $termsOfTrade
            ));
        }

        $this->exportDocumentCreationEnabled = true;
        $this->termsOfTrade = $termsOfTrade;
    }

    public function isExportDocumentCreationEnabled(): bool
    {
        return $this->exportDocumentCreationEnabled;
    }

    public function getTermsOfTrade(): string
    {
        return $this->termsOfTrade;
    }

    public function getShipmentDate(): DateTime
    {
        return $this->shipmentDate;
    }

    public function setShipmentDate(DateTime $shipmentDate): void
    {
        $this->shipmentDate = $shipmentDate;
    }

    private function createExportDocumentArray(): array
    {
        $parcelCustomsInformation = $this->parcel->getCustomsInformation();

        if (!$parcelCustomsInformation) {
            throw DhlApiClientException::missingCustomsInformationForParcel($this->parcel);
        }

        $euro = new Currency('EUR');
        foreach ($parcelCustomsInformation->getFees() as $feeType => $fee) {
            if (!$fee->getCurrency()->equals($euro)) {
                throw DhlApiClientException::feeGivenInUnsupportedCurrency($feeType, $fee->getCurrency());
            }
        }

        if (!$parcelCustomsInformation->getTypeOfShipment()) {
            throw DhlApiClientException::typeOfShipmentMissing();
        }

        // Create export document
        $exportDocument = [
            'exportType' => self::EXPORT_TYPE_MAPPING[$parcelCustomsInformation->getTypeOfShipment()],
            'exportTypeDescription' => $parcelCustomsInformation->getExplanationIfTypeOfShipmentIsOther(),
            'termsOfTrade' => $this->termsOfTrade,
            'placeOfCommital' => $parcelCustomsInformation->getOfficeOfOrigin(), // Sic! "Commital" is a typo by DHL!
            'additionalFee' => $parcelCustomsInformation->getTotalFees()->getValue(),
            'invoiceNumber' => implode(',', $parcelCustomsInformation->getInvoiceNumbers()),
            'permitNumber' => implode(',', $parcelCustomsInformation->getPermitNumbers()),
            'attestationNumber' => implode(',', $parcelCustomsInformation->getCertificateNumbers()),
            'sendersCustomsReference' => $this->senderAddress->getCustomsReference(),
            'addresseesCustomsReference' => $this->receiverAddress->getCustomsReference(),
            'ExportDocPosition' => [],
        ];

        foreach ($this->parcel->getItems() as $item) {
            $itemCustomsInformation = $item->getCustomsInformation();
            if ($itemCustomsInformation === null) {
                throw DhlApiClientException::missingCustomsInformationForParcelItem($item);
            }

            $customsValue = $itemCustomsInformation->getCustomsValue();
            if ($customsValue === null) {
                throw DhlApiClientException::missingCustomsValueForItem($item);
            }

            if (!$customsValue->getCurrency()->equals($euro)) {
                throw DhlApiClientException::customsValueGivenInUnsupportedCurrency(
                    $item,
                    $customsValue->getCurrency()
                );
            }

            if ($item->getUnitWeight() === null) {
                throw DhlApiClientException::missingWeightForItem($item);
            }

            $exportDocument['ExportDocPosition'][] = [
                'description' => $itemCustomsInformation->getDescription(),
                'countryCodeOrigin' => $itemCustomsInformation->getCountryIsoOfOrigin(),
                'customsTariffNumber' => $itemCustomsInformation->getTariffNumber(),
                'amount' => $item->getQuantity(),
                'customsValue' => round($customsValue->getValue(), 2),
                'netWeightInKG' => round($item->getUnitWeight()->convertTo('kg'), 3),
            ];
        }

        return $exportDocument;
    }

    public function getReceiverAddress(): Address
    {
        return $this->receiverAddress;
    }

    public function getSenderAddress(): Address
    {
        return $this->senderAddress;
    }

    public function getProduct(): DhlProduct
    {
        return $this->product;
    }

    public function getBillingInformation(): DhlBillingInformation
    {
        return $this->billingInformation;
    }

    public function getSequenceNumber(): string
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(string $sequenceNumber): void
    {
        $this->sequenceNumber = $sequenceNumber;
    }
}
