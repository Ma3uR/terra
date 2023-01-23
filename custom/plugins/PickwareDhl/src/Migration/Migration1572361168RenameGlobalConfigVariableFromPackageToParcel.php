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
 * Renames the keys of the configuration namespace "PickwareDhl.common":
 *  - weightCalculationFallbackPackageWeightInKg => weightCalculationFallbackParcelWeightInKg
 *  - weightCalculationFillerWeightPerPackageInKg => weightCalculationFillerWeightPerParcelInKg
 *  - weightCalculationMaxPackageWeightInKg => weightCalculationMaxParcelWeightInKg
 */
class Migration1572361168RenameGlobalConfigVariableFromPackageToParcel extends MigrationStep
{
    private const RENAMES = [
        'PickwareDhl.common.weightCalculationFallbackPackageWeightInKg' => 'PickwareDhl.common.weightCalculationFallbackParcelWeightInKg',
        'PickwareDhl.common.weightCalculationFillerWeightPerPackageInKg' => 'PickwareDhl.common.weightCalculationFillerWeightPerParcelInKg',
        'PickwareDhl.common.weightCalculationMaxPackageWeightInKg' => 'PickwareDhl.common.weightCalculationMaxParcelWeightInKg',
    ];

    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1572361168;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {
        $connection->beginTransaction();

        foreach (self::RENAMES as $oldKey => $newKey) {
            // See workaround in \Pickware\PickwareDhl\PickwareDhl::createDefaultConfiguration for why MD5 is used here.
            $connection->executeStatement(
                'UPDATE `system_config`
                SET `configuration_key` = :newKey,
                    `id` = IF(`sales_channel_id` IS NULL, UNHEX(MD5(:newKey)), `id`)
                WHERE
                    `configuration_key` = :oldKey',
                [
                    'newKey' => $newKey,
                    'oldKey' => $oldKey,
                ]
            );
        }

        $connection->commit();
    }

    /**
     * @param Connection $connection
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
