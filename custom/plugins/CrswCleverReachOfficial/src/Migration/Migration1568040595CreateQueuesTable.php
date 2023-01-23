<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1568040595CreateQueuesTable
 *
 * @package Crsw\CleverReachOfficial\Migration
 */
class Migration1568040595CreateQueuesTable extends MigrationStep
{
    public const QUEUES_TABLE = 'cleverreach_queues';

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1568040595;
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
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::QUEUES_TABLE . '` (
            `id` BINARY(16) NOT NULL,
            `internalId` BIGINT unsigned NOT NULL AUTO_INCREMENT,
            `priority` INT(11) NOT NULL DEFAULT 20,
            `status` VARCHAR(30) NOT NULL,
            `type` VARCHAR(100) NOT NULL,
            `queueName` VARCHAR(50) NOT NULL,
            `progress` INT(11) NOT NULL DEFAULT 0,
            `lastExecutionProgress` INT(11) DEFAULT 0,
            `retries` INT(11) NOT NULL DEFAULT 0,
            `failureDescription` VARCHAR(255),
            `serializedTask` MEDIUMBLOB NOT NULL,
            `createTimestamp` INT(11),
            `queueTimestamp` INT(11),
            `lastUpdateTimestamp` INT(11),
            `startTimestamp` INT(11),
            `finishTimestamp` INT(11),
            `failTimestamp` INT(11),
            PRIMARY KEY (`id`),
            UNIQUE (`internalId`)
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
        $sql = 'DROP TABLE IF EXISTS `' . self::QUEUES_TABLE . '`';
        $connection->executeQuery($sql);
    }
}
