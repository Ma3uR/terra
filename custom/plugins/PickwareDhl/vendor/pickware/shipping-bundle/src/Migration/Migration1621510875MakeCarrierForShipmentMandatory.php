<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * The field pickware_shipping_shipment.carrier_technical_name was nullable by accident. This migration removes the
 * nullability and also removes all shipments with no carrier. They should not exist anyway.
 */
class Migration1621510875MakeCarrierForShipmentMandatory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1621510875;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `pickware_shipping_shipment` WHERE `carrier_technical_name` IS NULL'
        );
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $connection->executeStatement(
                'ALTER TABLE `pickware_shipping_shipment`
                CHANGE `carrier_technical_name`
                    `carrier_technical_name` VARCHAR(255) NOT NULL'
            );
        } finally {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
