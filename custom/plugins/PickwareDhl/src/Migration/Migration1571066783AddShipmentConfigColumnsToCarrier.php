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
 * Adds the columns `shipment_config_default_values` and `shipment_config_options` to table `pickware_dhl_carrier`
 */
class Migration1571066783AddShipmentConfigColumnsToCarrier extends MigrationStep
{
    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1571066783;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_carrier`
            ADD `shipment_config_default_values` JSON NOT NULL AFTER `config_domain`,
            ADD `shipment_config_options` JSON NOT NULL AFTER `shipment_config_default_values`;'
        );
        $connection->executeStatement(
            'UPDATE `pickware_dhl_carrier`
            SET
                shipment_config_default_values = "{}",
                shipment_config_options = "{}"'
        );
    }

    /**
     * @param Connection $connection
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
