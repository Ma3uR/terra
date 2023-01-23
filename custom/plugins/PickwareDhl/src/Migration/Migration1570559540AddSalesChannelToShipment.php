<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds column `sales_channel_id` to table `pickware_dhl_shipment`.
 */
class Migration1570559540AddSalesChannelToShipment extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1570559540;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_shipment` ADD `sales_channel_id` BINARY(16) NULL DEFAULT NULL AFTER `carrier_technical_name`'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_shipment`
            ADD FOREIGN KEY(`sales_channel_id`) REFERENCES `sales_channel`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $connection->executeStatement(
            'UPDATE pickware_dhl_shipment shipment
            INNER JOIN pickware_dhl_shipment_order_delivery_mapping mapping ON shipment.id = mapping.shipment_id
            INNER JOIN order_delivery on mapping.order_delivery_id = order_delivery.id AND mapping.order_delivery_version_id = order_delivery.version_id
            INNER JOIN `order` ON order_delivery.order_id = `order`.id AND order_delivery.order_version_id = `order`.version_id
            SET shipment.sales_channel_id = `order`.sales_channel_id'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
