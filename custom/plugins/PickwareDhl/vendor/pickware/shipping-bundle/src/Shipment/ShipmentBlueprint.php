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

use JsonSerializable;
use Pickware\ShippingBundle\Parcel\Parcel;

class ShipmentBlueprint implements JsonSerializable
{
    /**
     * @var Address
     */
    private $senderAddress;

    /**
     * @var Address
     */
    private $receiverAddress;

    /**
     * @var Parcel[]
     */
    private $parcels = [];

    /**
     * @var string|null
     */
    private $carrierTechnicalName = null;

    /**
     * @var array
     */
    private $shipmentConfig = [];

    public function __construct()
    {
        $this->senderAddress = new Address();
        $this->receiverAddress = new Address();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'senderAddress' => $this->senderAddress,
            'receiverAddress' => $this->receiverAddress,
            'parcels' => $this->parcels,
            'carrierTechnicalName' => $this->carrierTechnicalName,
            'shipmentConfig' => $this->shipmentConfig,
        ];
    }

    /**
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $self = new self();
        $self->senderAddress = is_array($array['senderAddress'] ?? null) ? Address::fromArray($array['senderAddress']) : new Address();
        $self->receiverAddress = is_array($array['receiverAddress'] ?? null) ? Address::fromArray($array['receiverAddress']) : new Address();
        $self->parcels = array_map(function (array $parcelArray) {
            return Parcel::fromArray($parcelArray);
        }, $array['parcels'] ?? []);
        $self->carrierTechnicalName = isset($array['carrierTechnicalName']) ? strval($array['carrierTechnicalName']) : null;
        $self->shipmentConfig = is_array($array['shipmentConfig'] ?? null) ? $array['shipmentConfig'] : [];

        return $self;
    }

    /**
     * @return Address
     */
    public function getSenderAddress(): ?Address
    {
        return $this->senderAddress;
    }

    /**
     * @param Address $senderAddress
     */
    public function setSenderAddress(Address $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    /**
     * @return Address
     */
    public function getReceiverAddress(): Address
    {
        return $this->receiverAddress;
    }

    /**
     * @param Address $receiverAddress
     */
    public function setReceiverAddress(Address $receiverAddress): void
    {
        $this->receiverAddress = $receiverAddress;
    }

    /**
     * @return Parcel[]
     */
    public function getParcels(): array
    {
        return $this->parcels;
    }

    /**
     * @param Parcel $parcel
     */
    public function addParcel(Parcel $parcel): void
    {
        $this->parcels[] = $parcel;
    }

    /**
     * @param Parcel[] $parcels
     */
    public function setParcels(array $parcels): void
    {
        $this->parcels = $parcels;
    }

    /**
     * @return string|null
     */
    public function getCarrierTechnicalName(): ?string
    {
        return $this->carrierTechnicalName;
    }

    /**
     * @param string|null $carrierTechnicalName
     */
    public function setCarrierTechnicalName(?string $carrierTechnicalName): void
    {
        $this->carrierTechnicalName = $carrierTechnicalName;
    }

    /**
     * @return array
     */
    public function getShipmentConfig(): array
    {
        return $this->shipmentConfig;
    }

    /**
     * @param array $shipmentConfig
     */
    public function setShipmentConfig(array $shipmentConfig): void
    {
        $this->shipmentConfig = $shipmentConfig;
    }
}
