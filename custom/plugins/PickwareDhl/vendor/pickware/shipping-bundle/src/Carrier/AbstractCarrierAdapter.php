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

use Pickware\ShippingBundle\Carrier\Capabilities\CancellationCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\MultiTrackingCapability;
use Pickware\ShippingBundle\Carrier\Capabilities\ShipmentsRegistrationCapability;

abstract class AbstractCarrierAdapter implements ShipmentsRegistrationCapability
{
    public const CAPABILITY_CANCELLATION = 'cancellation';
    public const CAPABILITY_MULTI_TRACKING = 'multiTracking';
    public const CAPABILITY_SHIPMENTS_REGISTRATION = 'shipmentsRegistration';

    private const CAPABILITIES = [
        self::CAPABILITY_CANCELLATION => CancellationCapability::class,
        self::CAPABILITY_MULTI_TRACKING => MultiTrackingCapability::class,
        self::CAPABILITY_SHIPMENTS_REGISTRATION => ShipmentsRegistrationCapability::class,
    ];

    /**
     * @return string[]
     */
    public function getCapabilities(): array
    {
        $capabilities = [];

        foreach (self::CAPABILITIES as $capability => $interfaceName) {
            if ($this instanceof $interfaceName) {
                $capabilities[] = $capability;
            }
        }

        return $capabilities;
    }
}
