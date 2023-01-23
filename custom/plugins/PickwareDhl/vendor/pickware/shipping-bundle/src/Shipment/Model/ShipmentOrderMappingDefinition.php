<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment\Model;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class ShipmentOrderMappingDefinition extends MappingEntityDefinition
{
    /**
     * @inheritdoc
     */
    public function getEntityName(): string
    {
        return 'pickware_shipping_shipment_order_mapping';
    }

    /**
     * @inheritdoc
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('shipment_id', 'shipmentId', ShipmentDefinition::class))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required(), new PrimaryKey()),
            (new ReferenceVersionField(OrderDefinition::class, 'order_version_id'))->addFlags(new Required(), new PrimaryKey()),
            new ManyToOneAssociationField('shipment', 'shipment_id', ShipmentDefinition::class),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class),
        ]);
    }
}
