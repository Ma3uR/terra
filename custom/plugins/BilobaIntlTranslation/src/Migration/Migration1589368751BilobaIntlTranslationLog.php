<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1589368751BilobaIntlTranslationLog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589368751;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery('
            CREATE TABLE IF NOT EXISTS `biloba_intl_translation_log` (
              `id` BINARY(16) NOT NULL,
              `initiator` VARCHAR(255) NOT NULL,
              `entity_id` BINARY(16) NOT NULL,
              `entity_type` VARCHAR(255) NOT NULL,
              `type` VARCHAR(255) NOT NULL,
              `status` VARCHAR(255) NOT NULL,
              `context` JSON NOT NULL,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
