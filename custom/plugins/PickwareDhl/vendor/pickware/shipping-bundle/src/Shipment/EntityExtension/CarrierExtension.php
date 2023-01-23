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

use Pickware\ShippingBundle\Carrier\Model\CarrierDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CarrierExtension extends EntityExtension
{
    /**
     * @inheritdoc
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'shipments',
                ShipmentDefinition::class,
                'carrier_technical_name',
                'technical_name'
            ))->addFlags(new RestrictDelete())
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefinitionClass(): string
    {
        return CarrierDefinition::class;
    }
}
