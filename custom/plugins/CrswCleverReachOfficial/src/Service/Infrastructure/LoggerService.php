<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\LogData;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonologLogger;
use Shopware\Core\Kernel;

/**
 * Class LoggerService
 *
 * @package Crsw\CleverReachOfficial\Service\Infrastructure
 */
class LoggerService implements ShopLoggerAdapter
{
    /**
     * @var Kernel
     */
    private $kernel;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * LoggerService constructor.
     *
     * @param Kernel $kernel
     * @param Configuration $configuration
     */
    public function __construct(Kernel $kernel, Configuration $configuration)
    {
        $this->kernel = $kernel;
        $this->configuration = $configuration;
    }

    /**
     * Log message in the system.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data): void
    {
        $logLevel = $data->getLogLevel();
        if ($logLevel > $this->configuration->getMinLogLevel()) {
            return;
        }

        $logger = $this->getSystemLogger();
        $message = "[{$data->getComponent()}] {$data->getMessage()}";
        switch ($logLevel) {
            case Logger::ERROR:
                $logger->error($message);
                break;
            case Logger::WARNING:
                $logger->warning($message);
                break;
            case Logger::DEBUG:
                $logger->debug($message);
                break;
            default:
                $logger->info($message);
        }

    }

    /**
     * Returns system logger with predefined log directory and log file
     *
     * @return MonologLogger
     */
    private function getSystemLogger(): MonologLogger
    {
        $logger = new MonologLogger('cleverreach');
        $logFile = $this->kernel->getLogDir() . '/cleverreach/cleverreach.log';
        $logger->pushHandler(new RotatingFileHandler($logFile));

        return $logger;
    }
}
