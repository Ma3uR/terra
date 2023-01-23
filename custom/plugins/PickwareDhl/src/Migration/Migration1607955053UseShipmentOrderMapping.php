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

class Migration1607955053UseShipmentOrderMapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1607955053;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_shipment_order_mapping` (
                `shipment_id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NOT NULL,
                `order_version_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`shipment_id`,`order_id`,`order_version_id`),
                KEY `fk.pickware_dhl_shipment_order_mapping.shipment_id` (`shipment_id`),
                KEY `fk.pickware_dhl_shipment_order_mapping.order_id` (`order_id`,`order_version_id`),
                CONSTRAINT `fk.pickware_dhl_shipment_order_mapping.shipment_id` FOREIGN KEY (`shipment_id`) REFERENCES `pickware_dhl_shipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.pickware_dhl_shipment_order_mapping.order_delive` FOREIGN KEY (`order_id`,`order_version_id`) REFERENCES `order` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            INSERT INTO `pickware_dhl_shipment_order_mapping`
            SELECT
                `pickware_dhl_shipment_order_delivery_mapping`.`shipment_id`,
                `order_delivery`.`order_id`,
                `order_delivery`.`order_version_id`
            FROM `pickware_dhl_shipment_order_delivery_mapping`
            LEFT JOIN `order_delivery`
                ON `order_delivery`.`id` = `pickware_dhl_shipment_order_delivery_mapping`.`order_delivery_id`
                AND `order_delivery`.`version_id` = `pickware_dhl_shipment_order_delivery_mapping`.`order_delivery_version_id`;
        ');

        $connection->executeStatement('DROP TABLE `pickware_dhl_shipment_order_delivery_mapping`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
