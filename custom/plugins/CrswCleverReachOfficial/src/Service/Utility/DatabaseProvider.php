<?php

namespace Crsw\CleverReachOfficial\Service\Utility;

use Crsw\CleverReachOfficial\Migration\Migration1568040555CreateConfigsTable;
use Crsw\CleverReachOfficial\Migration\Migration1568040585CreateProcessesTable;
use Crsw\CleverReachOfficial\Migration\Migration1568040595CreateQueuesTable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Class DatabaseProvider
 *
 * @package Crsw\CleverReachOfficial\Service\Utility
 */
class DatabaseProvider
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * DatabaseProvider constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Remove all cleverreach tables
     *
     * @throws DBALException
     */
    public function removeCleverReachTables(): void
    {
        $this->removeTable(Migration1568040555CreateConfigsTable::CONFIGS_TABLE);
        $this->removeTable(Migration1568040595CreateQueuesTable::QUEUES_TABLE);
        $this->removeTable(Migration1568040585CreateProcessesTable::PROCESSES_TABLE);
    }

    /**
     * Removes table with given name
     *
     * @param string $tableName
     *
     * @throws DBALException
     */
    private function removeTable(string $tableName): void
    {
        $sql = 'DROP TABLE IF EXISTS `'. $tableName . '`';
        $this->connection->executeQuery($sql);
    }
}
