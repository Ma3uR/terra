<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Carrier;

use Exception;
use OutOfBoundsException;
use Pickware\ShippingBundle\Carrier\Capabilities\CancellationCapability;

class CarrierAdapterRegistry
{
    /**
     * @var AbstractCarrierAdapter[]
     */
    private $carrierAdapters = [];

    public function addCarrierAdapter(string $technicalName, AbstractCarrierAdapter $carrierAdapter): void
    {
        $this->carrierAdapters[$technicalName] = $carrierAdapter;
    }

    public function hasCarrierAdapter(string $technicalName): bool
    {
        return array_key_exists($technicalName, $this->carrierAdapters);
    }

    public function getCarrierAdapterByTechnicalName(string $technicalName): AbstractCarrierAdapter
    {
        if (!$this->hasCarrierAdapter($technicalName)) {
            throw new OutOfBoundsException(sprintf(
                'CarrierAdapter for Carrier with technical name "%s" is not installed',
                $technicalName
            ));
        }

        return $this->carrierAdapters[$technicalName];
    }

    public function getCancellationCapability(string $carrierTechnicalName): CancellationCapability
    {
        $carrierAdapter = $this->getCarrierAdapterByTechnicalName($carrierTechnicalName);
        if (!$carrierAdapter instanceof CancellationCapability) {
            throw new Exception(sprintf('Carrier "%s" is not capable of cancellations.', $carrierTechnicalName));
        }

        return $carrierAdapter;
    }
}
