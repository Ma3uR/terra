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

use InvalidArgumentException;
use JsonSerializable;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use VIISON\AddressSplitter\AddressSplitter;
use VIISON\AddressSplitter\Exceptions\SplittingException;

class Address implements JsonSerializable
{
    /**
     * @var string
     */
    private $firstName = '';

    /**
     * @var string
     */
    private $lastName = '';

    /**
     * @var string
     */
    private $company = '';

    /**
     * @var string
     */
    private $department = '';

    /**
     * @var string
     */
    private $addressAddition = '';

    /**
     * @var string
     */
    private $street = '';

    /**
     * @var string
     */
    private $houseNumber = '';

    /**
     * @var string
     */
    private $city = '';

    /**
     * @var string
     */
    private $zipCode = '';

    /**
     * @var string ISO 3166-1 alpha-2 (2 character) code (e.g. DE for Germany)
     */
    private $countryIso = '';

    /**
     * @var string ISO 3166-2 code (e.g. DE-HE for Hesse)
     */
    private $stateIso = '';

    /**
     * @var string
     */
    private $phone = '';

    /**
     * @var string
     */
    private $email = '';

    /**
     * A number to identify this person/company at the customs. E.g. the EORI-Number or TAX id. Usually not mandatory.
     *
     * @var string
     */
    private $customsReference = '';

    /**
     * @param OrderAddressEntity $orderAddress
     * @return Address
     */
    public static function fromShopwareOrderAddress(OrderAddressEntity $orderAddress): Address
    {
        $self = new self();

        $self->firstName = $orderAddress->getFirstName();
        $self->lastName = $orderAddress->getLastName();
        $self->company = $orderAddress->getCompany() ?: '';
        $self->department = $orderAddress->getDepartment() ?: '';
        $self->addressAddition = $orderAddress->getAdditionalAddressLine1() ?: '';
        if ($orderAddress->getAdditionalAddressLine2()) {
            $self->addressAddition .= '; ' . $orderAddress->getAdditionalAddressLine2();
        }
        $self->city = $orderAddress->getCity();
        $self->zipCode = $orderAddress->getZipcode();
        $self->countryIso = $orderAddress->getCountry()->getIso();
        $self->stateIso = $orderAddress->getCountryState() ? $orderAddress->getCountryState()->getShortCode() : '';
        $self->phone = $orderAddress->getPhoneNumber() ?: '';

        try {
            $splitAddress = AddressSplitter::splitAddress($orderAddress->getStreet());
            $self->street = $splitAddress['streetName'];
            $self->houseNumber = $splitAddress['houseNumber'];
        } catch (SplittingException $e) {
            $self->street = $orderAddress->getStreet();
        }

        return $self;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $self = new self();

        foreach ($array as $key => $value) {
            if (property_exists(self::class, $key)) {
                $self->$key = strval($value);
            }
        }

        return $self;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getAddressAddition(): string
    {
        return $this->addressAddition;
    }

    /**
     * @param string $addressAddition
     */
    public function setAddressAddition(string $addressAddition): void
    {
        $this->addressAddition = $addressAddition;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    /**
     * @param string $houseNumber
     */
    public function setHouseNumber(string $houseNumber): void
    {
        $this->houseNumber = $houseNumber;
    }

    /**
     * @return string
     */
    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string ISO 3166-1 alpha-2 (2 character) code (e.g. DE for Germany)
     */
    public function getCountryIso(): string
    {
        return $this->countryIso;
    }

    /**
     * @param string $countryIso ISO 3166-1 alpha-2 (2 character) code (e.g. DE for Germany)
     */
    public function setCountryIso(string $countryIso): void
    {
        $this->countryIso = $countryIso;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getCompany(): string
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getDepartment(): string
    {
        return $this->department;
    }

    /**
     * @param string $department
     */
    public function setDepartment(string $department): void
    {
        $this->department = $department;
    }

    /**
     * @return string
     */
    public function getHouseNumberBase(): string
    {
        try {
            $splitHouseNumber = AddressSplitter::splitHouseNumber($this->houseNumber);

            return $splitHouseNumber['base'];
        } catch (SplittingException $e) {
            return $this->houseNumber;
        }
    }

    /**
     * @return string
     */
    public function getHouseNumberExtension(): string
    {
        try {
            $splitHouseNumber = AddressSplitter::splitHouseNumber($this->houseNumber);

            return $splitHouseNumber['extension'];
        } catch (SplittingException $e) {
            return '';
        }
    }

    /**
     * @return string ISO 3166-2 code (e.g. DE-HE for Hesse)
     */
    public function getStateIso(): string
    {
        return $this->stateIso;
    }

    /**
     * @param string $stateIso ISO 3166-2 code (e.g. DE-HE for Hesse)
     */
    public function setStateIso(string $stateIso): void
    {
        $this->stateIso = $stateIso;
    }

    public function getCustomsReference(): string
    {
        return $this->customsReference;
    }

    public function setCustomsReference(string $customsReference): void
    {
        $this->customsReference = $customsReference;
    }

    /**
     * @return self
     */
    public function copyWithoutEmail(): self
    {
        $copy = clone $this;
        $copy->email = '';

        return $copy;
    }

    /**
     * @return self
     */
    public function copyWithoutPhone(): self
    {
        $copy = clone $this;
        $copy->phone = '';

        return $copy;
    }

    /**
     * Returns an array containing all name information from the address but with as few entries as possible.
     *
     * See unit tests for examples.
     *
     * @param string[]|null $keys Use these strings as keys instead of numeric keys
     * @return string[]
     */
    public function getOptimizedNameArray(?array $keys = null): array
    {
        $names = [
            sprintf('%s %s', $this->getFirstName(), $this->getLastName()),
            sprintf('%s, %s', $this->getCompany(), $this->getDepartment()),
            $this->getAddressAddition(),
        ];
        $names = array_values(array_filter(array_map(function ($name) {
            return trim($name, " \t\n\r\0\x0B" . ',');
        }, $names)));

        if ($keys === null) {
            return $names;
        }

        if (count($keys) !== 3) {
            throw new InvalidArgumentException(
                sprintf('Method %s currently only works with exactly 3 keys.', __METHOD__)
            );
        }

        $keys = array_slice($keys, 0, count($names));

        return array_combine($keys, $names);
    }
}
