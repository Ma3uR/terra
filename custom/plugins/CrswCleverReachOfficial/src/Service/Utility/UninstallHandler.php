<?php

namespace Crsw\CleverReachOfficial\Service\Utility;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\AuthProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigRepositoryService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Infrastructure\HttpClientService;

/**
 * Class UninstallHandler
 *
 * @package Crsw\CleverReachOfficial\Service\Utility
 */
class UninstallHandler
{
    /**
     * @var ConfigRepositoryService
     */
    private $configRepositoryService;
    /**
     * @var ConfigService
     */
    private $configService;
    /**
     * @var HttpClientService
     */
    private $httpClient;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var AuthProxy
     */
    private $authProxy;
    /**
     * @var ShopLoggerAdapter
     */
    private $logger;

    /**
     * UninstallHandler constructor.
     *
     * @param ConfigRepositoryService $configRepositoryService
     * @param ConfigService $configService
     * @param HttpClientService $httpClient
     * @param Proxy $proxy
     * @param AuthProxy $authProxy
     * @param ShopLoggerAdapter $logger
     */
    public function __construct(
        ConfigRepositoryService $configRepositoryService,
        ConfigService $configService,
        HttpClientService $httpClient,
        Proxy $proxy,
        AuthProxy $authProxy,
        ShopLoggerAdapter $logger
    ) {
        $this->configRepositoryService = $configRepositoryService;
        $this->configService = $configService;
        $this->httpClient = $httpClient;
        $this->proxy = $proxy;
        $this->authProxy = $authProxy;
        $this->logger = $logger;
    }


    /**
     * Removes product search endpoint, webhook event and revoke access token
     */
    public function removeCleverReachApiEndpointsAndRevokeToken(): void
    {
        $this->registerServices();
        try {
            if (!empty($this->configService->getAccessToken())) {
                $this->proxy->deleteProductSearchEndpoint($this->configService->getProductSearchContentId());
                $this->proxy->deleteReceiverEvent();
                $this->authProxy->revokeOAuth();
            }
        } catch (\Exception $exception) {
            Logger::logError("An error occurred during app uninstall: {$exception->getMessage()}", 'Integration');
        }
    }

    /**
     * Register required services for plugin uninstall
     */
    private function registerServices(): void
    {
        try {
            ServiceRegister::registerService(
                ConfigRepositoryInterface::CLASS_NAME,
                function () {
                    return $this->configRepositoryService;
                }
            );

            ServiceRegister::registerService(
                Configuration::CLASS_NAME,
                function () {
                    return $this->configService;
                }
            );

            ServiceRegister::registerService(
                HttpClient::CLASS_NAME,
                function () {
                    return $this->httpClient;
                }
            );

            ServiceRegister::registerService(
                ProxyInterface::CLASS_NAME,
                function () {
                    return $this->proxy;
                }
            );

            ServiceRegister::registerService(
                AuthProxy::CLASS_NAME,
                function () {
                    return $this->authProxy;
                }
            );

            ServiceRegister::registerService(
                ShopLoggerAdapter::CLASS_NAME,
                function () {
                    return $this->logger;
                }
            );

            ServiceRegister::registerService(
                DefaultLoggerAdapter::CLASS_NAME,
                function () {
                    return $this->logger;
                }
            );
        } catch (\InvalidArgumentException $exception) {
            // Ignore if service already registered
        }

    }

}