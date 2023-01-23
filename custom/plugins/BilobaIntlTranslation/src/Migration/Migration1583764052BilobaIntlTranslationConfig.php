<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1583764052BilobaIntlTranslationConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1583764052;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery('
            CREATE TABLE IF NOT EXISTS `biloba_intl_translation_config` (
              `id` BINARY(16) NOT NULL,
              `source_language_id` BINARY(16),
              `target_language_id` BINARY(16) NOT NULL,
              `translation_api` VARCHAR(255) NOT NULL,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`id`),
              CONSTRAINT `fk.biloba_intl_translation_config.target_language_id` FOREIGN KEY (`target_language_id`)
                REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.biloba_intl_translation_config.source_language_id` FOREIGN KEY (`source_language_id`)
                REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
