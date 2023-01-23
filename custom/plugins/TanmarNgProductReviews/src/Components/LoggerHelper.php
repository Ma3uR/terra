<?php

namespace Tanmar\ProductReviews\Components;

use Monolog\Logger;
use Tanmar\ProductReviews\Service\ConfigService;

class LoggerHelper {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ConfigService $configService
     * @param Logger $logger
     */
    public function __construct(ConfigService $configService, string $kernelEnv, Logger $logger) {
        $this->config = $configService->getConfig();
        $this->environment = $kernelEnv;
        $this->logger = $logger;
    }

    /**
     * @param int $logLevel
     * @param string $text
     * @param array $data
     */
    public function addDirectRecord(int $logLevel, string $text, array $data = []) {
        if ($this->config->getLoggingLevel() != 0 && $logLevel >= $this->config->getLoggingLevel()) {
            $this->logger->addRecord(
                    $logLevel,
                    'Product reviews ' . $text,
                    [
                        'source' => 'plugin',
                        'environment' => $this->environment,
                        'additionalData' => $data,
                    ]
            );
        }
    }

}
