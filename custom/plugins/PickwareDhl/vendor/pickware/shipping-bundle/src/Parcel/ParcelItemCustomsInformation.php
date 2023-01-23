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
use Pickware\MoneyBundle\MoneyValue;

class ParcelItemCustomsInformation implements JsonSerializable
{
    /**
     * A detailed description of each article in the item, e.g. "men's cotton shirts". General descriptions,
     * e.g. "spare parts", "samples" or "food products" are not permitted.
     *
     * Field number CN 23: 1
     * Field number on CN 22: 1
     *
     * @var string
     */
    private $description = '';

    /**
     * Customs value (Zollwert) of ONE item.
     *
     * This is neither the price of the product on the invoice, nor the net price. It is a completely independent value
     * that has to be entered by the user.
     *
     * Field number CN 23: 5 (Attention: the field requires you to put the TOTAL customs value of ALL items)
     * Field number on CN 22: 3 (Attention: the field requires you to put the TOTAL customs value of ALL items)
     *
     * @var MoneyValue|null
     */
    private $customsValue = null;

    /**
     * The HS tariff number (Zolltarifnummer)
     *
     * Field number CN 23: 7
     * Field number on CN 22: 4
     *
     * @var string|null
     */
    private $tariffNumber = null;

    /**
     * Country of origin of the item / Ursprungsland der Ware
     *
     * Field number CN 23: 8
     * Field number on CN 22: 5
     *
     * @var string|null ISO 3166-1 alpha-2 (2 character) code (e.g. DE for Germany)
     */
    private $countryIsoOfOrigin = null;

    /**
     * @param ParcelItem $parcelItem
     */
    public function __construct(ParcelItem $parcelItem)
    {
        $parcelItem->setCustomsInformation($this);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'description' => $this->description,
            'customsValue' => $this->customsValue,
            'tariffNumber' => $this->tariffNumber,
            'countryIsoOfOrigin' => $this->countryIsoOfOrigin,
        ];
    }

    /**
     * @param array $array
     * @param ParcelItem $parcelItem
     * @return static
     */
    public static function fromArray(array $array, ParcelItem $parcelItem): self
    {
        $self = new self($parcelItem);

        $self->setDescription($array['description']);
        $self->setCustomsValue($array['customsValue'] ? MoneyValue::fromArray($array['customsValue']) : null);
        $self->setTariffNumber($array['tariffNumber']);
        $self->setCountryIsoOfOrigin($array['countryIsoOfOrigin']);

        return $self;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return MoneyValue|null
     */
    public function getCustomsValue(): ?MoneyValue
    {
        return $this->customsValue;
    }

    /**
     * @param MoneyValue|null $customsValue
     */
    public function setCustomsValue(?MoneyValue $customsValue): void
    {
        $this->customsValue = $customsValue;
    }

    /**
     * @return string|null
     */
    public function getTariffNumber(): ?string
    {
        return $this->tariffNumber;
    }

    /**
     * @param string|null $tariffNumber
     */
    public function setTariffNumber(?string $tariffNumber): void
    {
        $this->tariffNumber = $tariffNumber;
    }

    /**
     * @return string|null
     */
    public function getCountryIsoOfOrigin(): ?string
    {
        return $this->countryIsoOfOrigin;
    }

    /**
     * @param string|null $countryIsoOfOrigin
     */
    public function setCountryIsoOfOrigin(?string $countryIsoOfOrigin): void
    {
        if ($countryIsoOfOrigin !== null && mb_strlen($countryIsoOfOrigin) !== 2) {
            throw new InvalidArgumentException(
                $countryIsoOfOrigin . ' is not a valid ISO 3166-1 alpha-2 (2 character) code'
            );
        }
        $this->countryIsoOfOrigin = $countryIsoOfOrigin ? mb_strtoupper($countryIsoOfOrigin) : null;
    }
}
