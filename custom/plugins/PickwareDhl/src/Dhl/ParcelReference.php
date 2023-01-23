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

class ParcelReference
{
    /**
     * @var string
     */
    private $shipmentId;

    /**
     * @var int
     */
    private $index;

    public function __construct(string $shipmentId, int $index)
    {
        $this->shipmentId = $shipmentId;
        $this->index = $index;
    }

    public function getShipmentId(): string
    {
        return $this->shipmentId;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function toString(): string
    {
        $binaryShipmentIdAsBase64 = base64_encode(hex2bin($this->shipmentId));

        return sprintf('%s_%d', $binaryShipmentIdAsBase64, $this->index);
    }

    public static function fromString(string $sequenceNumber): self
    {
        [
            $binaryShipmentIdAsBase64,
            $parcelIndex,
        ] = explode('_', $sequenceNumber);

        return new self(
            bin2hex(base64_decode($binaryShipmentIdAsBase64)),
            (int) $parcelIndex
        );
    }
}
