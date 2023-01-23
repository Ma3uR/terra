<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces
 */
interface LoggerAdapter
{
    /**
     * Log message in the system.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data);
}
