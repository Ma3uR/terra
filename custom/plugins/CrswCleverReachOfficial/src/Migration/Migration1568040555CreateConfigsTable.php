<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1568040555CreateConfigsTable
 *
 * @package Crsw\CleverReachOfficial\Migration
 */
class Migration1568040555CreateConfigsTable extends MigrationStep
{
    public const CONFIGS_TABLE = 'cleverreach_configs';

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1568040555;
    }

    /**
     * @inheritDoc
     *
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::CONFIGS_TABLE . '` (
            `id` BIGINT unsigned NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(255),
            `value` MEDIUMTEXT,
            PRIMARY KEY (`id`)
        ) 
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8
        COLLATE = utf8_general_ci;';

        $connection->executeQuery($sql);
    }

    /**
     * @inheritDoc
     *
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function updateDestructive(Connection $connection): void
    {
        $sql = 'DROP TABLE IF EXISTS `' . self::CONFIGS_TABLE . '`';
        $connection->executeQuery($sql);
    }
}
