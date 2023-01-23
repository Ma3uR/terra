<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Parcel;

use InvalidArgumentException;
use JsonSerializable;
use Pickware\MoneyBundle\Currency;
use Pickware\MoneyBundle\CurrencyConverter;
use Pickware\MoneyBundle\MoneyValue;
use Pickware\UnitsOfMeasurement\Dimensions\BoxDimensions;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;

class Parcel implements JsonSerializable
{
    /**
     * @var ParcelItem[]
     */
    private $items = [];

    /**
     * @var BoxDimensions|null
     */
    private $dimensions = null;

    /**
     * @var Weight
     */
    private $fillerWeight;

    /**
     * @var Weight|null
     */
    private $weightOverwrite = null;

    /**
     * @var string|null
     */
    private $customerReference = null;

    /**
     * @var ParcelCustomsInformation|null
     */
    private $customsInformation = null;

    public function __construct()
    {
        $this->fillerWeight = new Weight(0, 'kg');
    }

    public function jsonSerialize(): array
    {
        return [
            'items' => $this->items,
            'dimensions' => $this->dimensions,
            'fillerWeight' => $this->fillerWeight,
            'weightOverwrite' => $this->weightOverwrite,
            'customerReference' => $this->customerReference,
            'customsInformation' => $this->customsInformation,
        ];
    }

    public static function fromArray(array $array): self
    {
        $self = new self();
        $self->setDimensions(isset($array['dimensions']) ? BoxDimensions::fromArray($array['dimensions']) : null);
        $self->setFillerWeight(
            isset($array['fillerWeight']) ? Weight::fromArray($array['fillerWeight']) : new Weight(0, 'kg')
        );
        $self->setWeightOverwrite(
            isset($array['weightOverwrite']) ? Weight::fromArray($array['weightOverwrite']) : null
        );
        $self->setCustomerReference($array['customerReference'] ?? null);
        if (isset($array['customsInformation'])) {
            $self->setCustomsInformation(ParcelCustomsInformation::fromArray($array['customsInformation'], $self));
        } else {
            $self->setCustomsInformation(null);
        }
        $self->setItems(array_map(function (array $itemArray) {
            return ParcelItem::fromArray($itemArray);
        }, $array['items'] ?? []));

        return $self;
    }

    /**
     * Creates a copy of the parcel but without any item
     *
     * @return self
     */
    public function createCopyWithoutItems(): self
    {
        $self = new self();
        $self->fillerWeight = $this->fillerWeight;
        $self->dimensions = $this->dimensions;
        $self->weightOverwrite = $this->weightOverwrite;
        $self->customerReference = $this->customerReference;
        if ($this->customsInformation) {
            $this->customsInformation->copyToParcel($self);
        }

        return $self;
    }

    public function getTotalWeight(): ?Weight
    {
        if ($this->weightOverwrite) {
            return $this->weightOverwrite;
        }

        $weights = array_map(function (ParcelItem $parcelItem) {
            return $parcelItem->getTotalWeight();
        }, $this->items);

        if (in_array(null, $weights, true)) {
            return null;
        }

        if ($this->fillerWeight !== null) {
            $weights[] = $this->fillerWeight;
        }

        return Weight::sum(...$weights);
    }

    /**
     * @return string Returns a human readable description of this package
     */
    public function getDescription(): string
    {
        return sprintf(
            'Parcel with items %s',
            implode(', ', array_map(function (ParcelItem $item) {
                return $item->getName();
            }, $this->items))
        );
    }

    /**
     * @return ParcelItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param ParcelItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem(ParcelItem $item): void
    {
        $this->items[] = $item;
    }

    public function getDimensions(): ?BoxDimensions
    {
        return $this->dimensions;
    }

    public function setDimensions(?BoxDimensions $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getFillerWeight(): Weight
    {
        return $this->fillerWeight;
    }

    public function setFillerWeight(Weight $fillerWeight): void
    {
        $this->fillerWeight = $fillerWeight;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    public function setCustomerReference(?string $customerReference): void
    {
        $this->customerReference = $customerReference;
    }

    public function getWeightOverwrite(): ?Weight
    {
        return $this->weightOverwrite;
    }

    public function setWeightOverwrite(?Weight $weightOverwrite): void
    {
        $this->weightOverwrite = $weightOverwrite;
    }

    public function getCustomsInformation(): ?ParcelCustomsInformation
    {
        return $this->customsInformation;
    }

    public function setCustomsInformation(?ParcelCustomsInformation $customsInformation): void
    {
        if ($customsInformation && $customsInformation->getParcel() !== $this) {
            throw new InvalidArgumentException(sprintf(
                'The referenced %s in passed %s is not $this.',
                self::class,
                ParcelCustomsInformation::class
            ));
        }
        $this->customsInformation = $customsInformation;
    }

    public function convertAllMoneyValuesToSameCurrency(
        CurrencyConverter $currencyConverter,
        Currency $targetCurrency
    ): void {
        if ($this->customsInformation) {
            $fees = array_map(
                function (MoneyValue $moneyValue) use ($targetCurrency, $currencyConverter) {
                    return $currencyConverter->convertMoneyValueToCurrency($moneyValue, $targetCurrency);
                },
                $this->customsInformation->getFees()
            );
            $this->customsInformation->setFees($fees);
        }

        foreach ($this->getItems() as $item) {
            $customsInformation = $item->getCustomsInformation();
            if (!$customsInformation || !$customsInformation->getCustomsValue()) {
                continue;
            }
            $customsInformation->setCustomsValue($currencyConverter->convertMoneyValueToCurrency(
                $customsInformation->getCustomsValue(),
                $targetCurrency
            ));
        }
    }
}
