<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment\EntityExtension;

use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentOrderMappingDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderExtension extends EntityExtension
{
    /**
     * @inheritdoc
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'pickwareShippingShipments',
                ShipmentDefinition::class,
                ShipmentOrderMappingDefinition::class,
                'order_id',
                'shipment_id'
            ))->addFlags(new CascadeDelete())
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}
