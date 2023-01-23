<?php

namespace Crsw\CleverReachOfficial\Service\Utility;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Attributes;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\OrderItems;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Recipients;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\AuthProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskRunner;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\EventBus;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\GuidProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class Initializer
 *
 * @package Crsw\CleverReachOfficial\Service\Utility
 */
class Initializer
{
    /**
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * @var Queue
     */
    private $queue;
    /**
     * @var TaskRunnerWakeup
     */
    private $taskRunnerWakeUp;
    /**
     * @var TaskRunner
     */
    private $taskRunner;
    /**
     * @var GuidProvider
     */
    private $guidProvider;
    /**
     * @var DefaultLoggerAdapter
     */
    private $defaultLogger;
    /**
     * @var TaskRunnerStatusStorage
     */
    private $taskRunnerStatusStorage;
    /**
     * @var ShopLoggerAdapter
     */
    private $shopLoggerAdapter;
    /**
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var AsyncProcessStarter
     */
    private $asyncProcessStarter;
    /**
     * @var TaskQueueStorage
     */
    private $taskQueueStorage;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var ConfigRepositoryInterface
     */
    private $configRepository;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var Attributes
     */
    private $attributeService;
    /**
     * @var Recipients
     */
    private $recipientService;
    /**
     * @var OrderItems
     */
    private $orderItemService;
    /**
     * @var AuthProxy
     */
    private $authProxy;

    /**
     * Initializer constructor.
     *
     * @param TimeProvider $timeProvider
     * @param Queue $queue
     * @param TaskRunnerWakeup $taskRunnerWakeUp
     * @param TaskRunner $taskRunner
     * @param GuidProvider $guidProvider
     * @param DefaultLoggerAdapter $defaultLogger
     * @param TaskRunnerStatusStorage $taskRunnerStatusStorage
     * @param ShopLoggerAdapter $shopLoggerAdapter
     * @param HttpClient $httpClient
     * @param AsyncProcessStarter $asyncProcessStarter
     * @param TaskQueueStorage $taskQueueStorage
     * @param Proxy $proxy
     * @param ConfigRepositoryInterface $configRepository
     * @param Configuration $configService
     * @param Serializer $serializer
     * @param Attributes $attributeService
     * @param Recipients $recipientsService
     * @param OrderItems $orderItemService
     * @param AuthProxy $authProxy
     */
    public function __construct(
        TimeProvider $timeProvider,
        Queue $queue,
        TaskRunnerWakeup $taskRunnerWakeUp,
        TaskRunner $taskRunner,
        GuidProvider $guidProvider,
        DefaultLoggerAdapter $defaultLogger,
        TaskRunnerStatusStorage $taskRunnerStatusStorage,
        ShopLoggerAdapter $shopLoggerAdapter,
        HttpClient $httpClient,
        AsyncProcessStarter $asyncProcessStarter,
        TaskQueueStorage $taskQueueStorage,
        Proxy $proxy,
        ConfigRepositoryInterface $configRepository,
        Configuration $configService,
        Serializer $serializer,
        Attributes $attributeService,
        Recipients $recipientsService,
        OrderItems $orderItemService,
        AuthProxy $authProxy
    ) {
        $this->timeProvider = $timeProvider;
        $this->queue = $queue;
        $this->taskRunnerWakeUp = $taskRunnerWakeUp;
        $this->taskRunner = $taskRunner;
        $this->guidProvider = $guidProvider;
        $this->defaultLogger = $defaultLogger;
        $this->taskRunnerStatusStorage = $taskRunnerStatusStorage;
        $this->shopLoggerAdapter = $shopLoggerAdapter;
        $this->httpClient = $httpClient;
        $this->asyncProcessStarter = $asyncProcessStarter;
        $this->taskQueueStorage = $taskQueueStorage;
        $this->proxy = $proxy;
        $this->configRepository = $configRepository;
        $this->configService = $configService;
        $this->serializer = $serializer;
        $this->attributeService = $attributeService;
        $this->recipientService = $recipientsService;
        $this->orderItemService = $orderItemService;
        $this->authProxy = $authProxy;
    }


    /**
     * Register all services
     */
    public function registerServices()
    {
        try {
            $this->registerInfrastructureServices();
            $this->registerBusinessServices();
        } catch (\InvalidArgumentException $exception) {
            //
        }
    }

    /**
     * Register all services
     */
    private function registerInfrastructureServices()
    {
        ServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );

        ServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return $this->serializer;
            }
        );

        ServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () {
                return $this->timeProvider;
            }
        );

        ServiceRegister::registerService(
            Queue::CLASS_NAME,
            function () {
                return $this->queue;
            }
        );

        ServiceRegister::registerService(
            TaskRunnerWakeUp::CLASS_NAME,
            function () {
                return $this->taskRunnerWakeUp;
            }
        );

        ServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () {
                return $this->taskRunner;
            }
        );

        ServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () {
                return $this->guidProvider;
            }
        );

        ServiceRegister::registerService(
            DefaultLoggerAdapter::CLASS_NAME,
            function () {
                return $this->defaultLogger;
            }
        );

        ServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () {
                return $this->taskRunnerStatusStorage;
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return $this->httpClient;
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return $this->shopLoggerAdapter;
            }
        );

        ServiceRegister::registerService(
            AsyncProcessStarter::CLASS_NAME,
            function () {
                return $this->asyncProcessStarter;
            }
        );

        ServiceRegister::registerService(
            TaskQueueStorage::CLASS_NAME,
            function () {
                return $this->taskQueueStorage;
            }
        );

        ServiceRegister::registerService(
            ConfigRepositoryInterface::CLASS_NAME,
            function () {
                return $this->configRepository;
            }
        );

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () {
                return $this->configService;
            }
        );
    }

    private function registerBusinessServices()
    {
        ServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () {
                return $this->proxy;
            }
        );

        ServiceRegister::registerService(
            Attributes::CLASS_NAME,
            function () {
                return $this->attributeService;
            }
        );

        ServiceRegister::registerService(
            Recipients::CLASS_NAME,
            function () {
                return $this->recipientService;
            }
        );

        ServiceRegister::registerService(
            OrderItems::CLASS_NAME,
            function () {
                return $this->orderItemService;
            }
        );

        ServiceRegister::registerService(
            AuthProxy::CLASS_NAME,
            function () {
                return $this->authProxy;
            }
        );
    }
}