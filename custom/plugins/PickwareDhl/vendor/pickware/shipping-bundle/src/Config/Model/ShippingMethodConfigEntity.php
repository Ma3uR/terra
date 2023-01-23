<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Config\Model;

use Pickware\ShippingBundle\Carrier\Model\CarrierEntity;
use Pickware\ShippingBundle\ParcelPacking\ParcelPackingConfiguration;
use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ShippingMethodConfigEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var ShippingMethodEntity
     */
    protected $shippingMethod;

    /**
     * @var string
     */
    protected $shippingMethodId;

    /**
     * @var CarrierEntity
     */
    protected $carrier;

    /**
     * @var string
     */
    protected $carrierTechnicalName;

    /**
     * @var array
     */
    protected $shipmentConfig;

    /**
     * @var ParcelPackingConfiguration
     */
    protected $parcelPackingConfiguration;

    /**
     * @return ShippingMethodEntity
     */
    public function getShippingMethod(): ShippingMethodEntity
    {
        if ($this->shippingMethod === null && $this->shippingMethodId !== null) {
            throw new AssociationNotLoadedException('shippingMethod', $this);
        }

        return $this->shippingMethod;
    }

    /**
     * @param ShippingMethodEntity $shippingMethod
     */
    public function setShippingMethod(ShippingMethodEntity $shippingMethod): void
    {
        $this->shippingMethodId = $shippingMethod->getId();
        $this->shippingMethod = $shippingMethod;
    }

    /**
     * @return string
     */
    public function getShippingMethodId(): string
    {
        return $this->shippingMethodId;
    }

    /**
     * @param string $shippingMethodId
     */
    public function setShippingMethodId(string $shippingMethodId): void
    {
        if ($this->shippingMethod !== null && $this->shippingMethod->getId() !== $shippingMethodId) {
            $this->shippingMethod = null;
        }
        $this->shippingMethodId = $shippingMethodId;
    }

    /**
     * @return CarrierEntity
     */
    public function getCarrier(): CarrierEntity
    {
        if ($this->carrier === null && $this->carrierTechnicalName !== null) {
            throw new AssociationNotLoadedException('carrier', $this);
        }

        return $this->carrier;
    }

    /**
     * @param CarrierEntity $carrier
     */
    public function setCarrier(CarrierEntity $carrier): void
    {
        $this->carrier = $carrier;
        $this->carrierTechnicalName = $carrier->getTechnicalName();
    }

    /**
     * @return string
     */
    public function getCarrierTechnicalName(): string
    {
        return $this->carrierTechnicalName;
    }

    /**
     * @param string $carrierTechnicalName
     */
    public function setCarrierTechnicalName(string $carrierTechnicalName): void
    {
        if ($this->carrier !== null && $this->carrier->getTechnicalName() !== $carrierTechnicalName) {
            $this->carrier = null;
        }
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

    public function getParcelPackingConfiguration(): ParcelPackingConfiguration
    {
        return $this->parcelPackingConfiguration;
    }

    public function setParcelPackingConfiguration(ParcelPackingConfiguration $parcelPackingConfiguration): void
    {
        $this->parcelPackingConfiguration = $parcelPackingConfiguration;
    }
}
