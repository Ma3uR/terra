<?php declare(strict_types=1);

namespace TerraAustralia\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604574430CategoryHeaderWidget extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1604574430;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery(
            '
            CREATE TABLE IF NOT EXISTS `tr_category_header_widget` (
            `id` BINARY(16) NOT NULL,
            `category_id` BINARY(16) NOT NULL,
            `source` MEDIUMTEXT DEFAULT NULL,
            
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`, `category_id`),
            
            CONSTRAINT `fk.tr_category_header_widget.category_id` FOREIGN KEY (`category_id`)
                REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        '
        );
        
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
