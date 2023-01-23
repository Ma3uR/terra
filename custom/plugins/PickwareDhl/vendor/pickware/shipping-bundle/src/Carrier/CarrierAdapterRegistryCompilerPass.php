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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CarrierAdapterRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(CarrierAdapterRegistry::class)) {
            return;
        }

        $carrierRegistryDefinition = $container->findDefinition(CarrierAdapterRegistry::class);
        $carriers = $container->findTaggedServiceIds('pickware_shipping.carrier_adapter');
        foreach ($carriers as $carrierAdapterClassName => $tagAttributes) {
            $technicalName = $tagAttributes[0]['technicalName'];
            $carrierRegistryDefinition->addMethodCall('addCarrierAdapter', [$technicalName, new Reference($carrierAdapterClassName)]);
        }
    }
}
