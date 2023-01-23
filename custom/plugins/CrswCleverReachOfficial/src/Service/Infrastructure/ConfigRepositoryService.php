<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Entity\Config\ConfigEntityRepository;

/**
 * Class ConfigRepositoryService
 *
 * @package Crsw\CleverReachOfficial\Service\Infrastructure
 */
class ConfigRepositoryService implements ConfigRepositoryInterface
{
    /**
     * @var ConfigEntityRepository
     */
    private $configRepository;

    /**
     * ConfigRepositoryService constructor.
     *
     * @param ConfigEntityRepository $configRepository
     */
    public function __construct(ConfigEntityRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * Get configuration by key.
     *
     * @param string $key Unique key of configuration.
     *
     * @return string|int
     *  Configuration value.
     */
    public function get($key)
    {
        try {
            $configuration = $this->configRepository->getConfigByKey($key);

            return $configuration['value'] ?? null;
        } catch (\Exception $exception) {
            Logger::logError(
                "An error occurred when trying to fetch config value from database: {$exception->getMessage()}",
                'Integration'
            );

            return null;
        }
    }

    /**
     * Set configuration by key and value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function set($key, $value): bool
    {
        try {
            $this->configRepository->saveValue($key, $value);

            return true;
        } catch (\Exception $exception) {
            Logger::logError(
                "An error occurred when trying to save config value into database: {$exception->getMessage()}",
                'Integration'
            );

            return false;
        }
    }
}
