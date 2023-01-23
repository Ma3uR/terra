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

class Migration1562148231CreateCarrierSchema extends MigrationStep
{
    /**
     * @inheritDoc
     */
    public function getCreationTimestamp(): int
    {
        return 1562148231;
    }

    /**
     * @inheritDoc
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_carrier` (
                `id` BINARY(16) NOT NULL,
                `technical_name` VARCHAR(255) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `abbreviation` VARCHAR(255) NOT NULL,
                `config_domain` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `unique.technical_name` UNIQUE (`technical_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    /**
     * @inheritDoc
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
