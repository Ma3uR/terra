<?php declare(strict_types=1);

namespace Crsw\CleverReachOfficial\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1570480152SetInitialConfig
 *
 * @package Crsw\CleverReachOfficial\Migration
 */
class Migration1570480152SetInitialConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1570480152;
    }

    /**
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $this->setTaskRunnerStatus($connection);
        $this->setProductSearchPassword($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    /**
     * @param Connection $connection
     *
     * @throws DBALException
     * @throws \Exception
     */
    private function setTaskRunnerStatus(Connection $connection): void
    {
        $values = json_encode(['guid' => '', 'timestamp' => null]);
        $connection->insert('cleverreach_configs', ['`key`' => 'CLEVERREACH_TASK_RUNNER_STATUS', '`value`' => $values]);
    }

    /**
     * @param Connection $connection
     *
     * @throws DBALException
     * @throws \Exception
     */
    private function setProductSearchPassword(Connection $connection): void
    {
        $connection->insert('cleverreach_configs', ['`key`' => 'CLEVERREACH_PRODUCT_SEARCH_PASSWORD', '`value`' => sha1((string)time())]);
    }
}
