<?php

namespace Crsw\CleverReachOfficial\Entity\Config;

use Crsw\CleverReachOfficial\Migration\Migration1568040555CreateConfigsTable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;

/**
 * Class ConfigEntityRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Config
 */
class ConfigEntityRepository
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $tableName;

    /**
     * ConfigEntityRepository constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->tableName = Migration1568040555CreateConfigsTable::CONFIGS_TABLE;
    }

    /**
     * Saves new configuration
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     * @throws DBALException
     * @throws InconsistentCriteriaIdsException
     */
    public function saveValue(string $key, $value): void
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }

        $existingConfiguration = $this->getConfigByKey($key);
        $data = ['`key`' => $key, '`value`' => $value];
        if (!empty($existingConfiguration)) {
            $this->connection->update($this->tableName, $data, ['`id`' => $existingConfiguration['id']]);
        } else {
            $this->connection->insert($this->tableName, $data);
        }
    }

    /**
     * Returns config entity by key
     *
     * @param string $key
     *
     * @return array|bool
     * @throws DBALException
     */
    public function getConfigByKey(string $key)
    {
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `key` = ?";

        return $this->connection->fetchAssoc($sql, [$key]);
    }
}