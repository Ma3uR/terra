<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Logger;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class DefaultLogger
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Logger
 */
class DefaultLogger implements DefaultLoggerAdapter
{
    /**
     * Sending log data to CleverReach API.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data)
    {
        /** @var HttpClient $httpClient */
        $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
        // Waiting on CR to define API endpoint
        $httpClient->requestAsync('POST', '', array(), json_encode(get_object_vars($data)));
    }
}
