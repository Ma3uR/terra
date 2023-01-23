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

class Migration1563203721CreateConfigSchema extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1563203721;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'CREATE TABLE `pickware_dhl_shipping_method_config` (
                `id` BINARY(16) NOT NULL,
                `shipping_method_id` BINARY(16) NOT NULL,
                `carrier_technical_name` VARCHAR(16) NOT NULL,
                `shipment_config` JSON NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.pickware_dhl_shipping_method_config.shipment_config` CHECK (JSON_VALID(`shipment_config`)),
                KEY `fk.pickware_dhl_shipping_method_config.carrier_technical_name` (`carrier_technical_name`),
                UNIQUE `fk.pickware_dhl_shipping_method_config.shipping_method_id` (`shipping_method_id`),
                CONSTRAINT `fk.pickware_dhl_shipping_method_config.carrier_technical_name` FOREIGN KEY (`carrier_technical_name`) REFERENCES `pickware_dhl_carrier` (`technical_name`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.pickware_dhl_shipping_method_config.shipping_method_id` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
