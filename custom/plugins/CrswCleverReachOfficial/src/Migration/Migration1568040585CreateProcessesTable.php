<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1568040585CreateProcessesTable
 *
 * @package Crsw\CleverReachOfficial\Migration
 */
class Migration1568040585CreateProcessesTable extends MigrationStep
{
    public const PROCESSES_TABLE = 'cleverreach_processes';

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1568040585;
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
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::PROCESSES_TABLE . '` (
            `id` BINARY(16) NOT NULL,
            `guid` VARCHAR(50) NOT NULL,
            `runner` VARCHAR(500) NOT NULL,
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
        $sql = 'DROP TABLE IF EXISTS `' . self::PROCESSES_TABLE . '`';
        $connection->executeQuery($sql);
    }
}
