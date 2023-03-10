<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required;

/**
 * Interface ConfigRepositoryInterface
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required
 */
interface ConfigRepositoryInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * Get configuration by key.
     *
     * @param string $key Unique key of configuration.
     *
     * @return string|int
     *  Configuration value.
     */
    public function get($key);

    /**
     * Set configuration by key and value.
     *
     * @param string $key Unique key of configuration.
     * @param mixed $value Value for passed $key.
     *
     * @return int|bool
     *   Configuration value.
     */
    public function set($key, $value);
}