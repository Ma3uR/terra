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

use JsonSerializable;
use Pickware\UnitsOfMeasurement\Dimensions\BoxDimensions;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;

class ParcelItem implements JsonSerializable
{
    /**
     * @var string|null
     */
    private $name = null;

    /**
     * @var Weight|null
     */
    private $unitWeight = null;

    /**
     * @var BoxDimensions|null
     */
    private $unitDimensions = null;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var ParcelItemCustomsInformation|null
     */
    private $customsInformation = null;

    /**
     * @param int $quantity
     */
    public function __construct(int $quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'unitWeight' => $this->unitWeight,
            'unitDimensions' => $this->unitDimensions,
            'quantity' => $this->quantity,
            'customsInformation' => $this->customsInformation,
        ];
    }

    /**
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $self = new self(intval($array['quantity'] ?? 0));

        $self->setName($array['name']);
        $self->setUnitWeight(isset($array['unitWeight']) ? Weight::fromArray($array['unitWeight']) : null);
        $self->setUnitDimensions(isset($array['unitDimensions']) ? BoxDimensions::fromArray($array['unitDimensions']) : null);
        $self->setCustomsInformation(isset($array['customsInformation']) ? ParcelItemCustomsInformation::fromArray($array['customsInformation'], $self) : null);

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function __clone()
    {
        if ($this->unitWeight) {
            $this->unitWeight = clone $this->unitWeight;
        }
        if ($this->unitDimensions) {
            $this->unitDimensions = clone $this->unitDimensions;
        }
    }

    /**
     * @return Weight|null
     */
    public function getTotalWeight(): ?Weight
    {
        if (!$this->getUnitWeight()) {
            return null;
        }

        return $this->unitWeight->multiplyWithScalar($this->quantity);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Weight|null
     */
    public function getUnitWeight(): ?Weight
    {
        return $this->unitWeight;
    }

    /**
     * @param Weight|null $unitWeight
     */
    public function setUnitWeight(?Weight $unitWeight): void
    {
        $this->unitWeight = $unitWeight;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return BoxDimensions|null
     */
    public function getUnitDimensions(): ?BoxDimensions
    {
        return $this->unitDimensions;
    }

    /**
     * @param BoxDimensions|null $unitDimensions
     */
    public function setUnitDimensions(?BoxDimensions $unitDimensions): void
    {
        $this->unitDimensions = $unitDimensions;
    }

    /**
     * @return ParcelItemCustomsInformation|null
     */
    public function getCustomsInformation(): ?ParcelItemCustomsInformation
    {
        return $this->customsInformation;
    }

    /**
     * @param ParcelItemCustomsInformation|null $customsInformation
     */
    public function setCustomsInformation(?ParcelItemCustomsInformation $customsInformation): void
    {
        $this->customsInformation = $customsInformation;
    }
}
