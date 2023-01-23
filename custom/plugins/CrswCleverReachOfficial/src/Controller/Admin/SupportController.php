<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Service\Infrastructure\TaskQueueStorageService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SupportController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class SupportController extends AbstractController
{

    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var TaskQueueStorageService
     */
    private $taskQueueStorage;
    /**
     * @var TaskRunnerWakeup
     */
    private $taskRunnerWakeUp;

    /**
     * SupportController constructor.
     *
     * @param Configuration $configService
     * @param TaskQueueStorage $taskQueueStorage
     * @param Initializer $initializer
     * @param TaskRunnerWakeup $taskRunnerWakeUp
     */
    public function __construct(
        Configuration $configService,
        TaskQueueStorage $taskQueueStorage,
        Initializer $initializer,
        TaskRunnerWakeup $taskRunnerWakeUp
    ) {
        $initializer->registerServices();
        $this->configService = $configService;
        $this->taskQueueStorage = $taskQueueStorage;
        $this->taskRunnerWakeUp = $taskRunnerWakeUp;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/support", name="api.cleverreach.support", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     * @throws QueueItemDeserializationException
     */
    public function index(Request $request): JsonApiResponse
    {
        if ($request->getMethod() === 'GET') {
            return $this->getConfigValues();
        }

        return $this->updateConfigValues($request);
    }

    /**
     * @return JsonApiResponse
     * @throws QueueItemDeserializationException
     */
    private function getConfigValues(): JsonApiResponse
    {
        $configData = [
            'integrationId' => $this->configService->getIntegrationId(),
            'integrationName' => $this->configService->getIntegrationName(),
            'minLogLevel' => $this->configService->getMinLogLevel(),
            'isProductSearchEnabled' => $this->configService->isProductSearchEnabled(),
            'productSearchParameters' => $this->configService->getProductSearchParameters(),
            'recipientsSynchronizationBatchSize' => $this->configService->getRecipientsSynchronizationBatchSize(),
            'maxStartedTasksLimit' => $this->configService->getMaxStartedTasksLimit(),
            'maxTaskExecutionRetries' => $this->configService->getMaxTaskExecutionRetries(),
            'maxTaskInactivityPeriod' => $this->configService->getMaxTaskInactivityPeriod(),
            'taskRunnerMaxAliveTime' => $this->configService->getTaskRunnerMaxAliveTime(),
            'taskRunnerStatus' => $this->configService->getTaskRunnerStatus(),
            'taskRunnerWakeupDelay' => $this->configService->getTaskRunnerWakeupDelay(),
            'queueName' => $this->configService->getQueueName(),
            'clientId' => $this->configService->getClientId(),
            'clientSecret' => $this->configService->getClientSecret(),
            'eventHandlerUrl' => $this->configService->getCrEventHandlerURL(),
            'asyncProcessTimeout' => $this->configService->getAsyncProcessRequestTimeout(),
            'accessToken' => $this->configService->getAccessToken(),
            'isOnline' => $this->configService->isUserOnline(),
            'oldestQueuedItems' => $this->getOldestQueuedItems(),
            'allQueueItems' => $this->getAllQueueItems(),
        ];

        return new JsonApiResponse($configData);
    }

    /**
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    private function updateConfigValues(Request $request): JsonApiResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (isset($payload['minLogLevel'])) {
            $this->configService->saveMinLogLevel((int)$payload['minLogLevel']);
        }

        if (isset($payload['defaultLoggerStatus'])) {
            $this->configService->setDefaultLoggerEnabled($payload['defaultLoggerStatus']);
        }

        if (isset($payload['maxStartedTasksLimit'])) {
            $this->configService->setMaxStartedTaskLimit($payload['maxStartedTasksLimit']);
        }

        if (isset($payload['taskRunnerWakeUpDelay'])) {
            $this->configService->setTaskRunnerWakeUpDelay($payload['taskRunnerWakeUpDelay']);
        }

        if (isset($payload['taskRunnerMaxAliveTime'])) {
            $this->configService->setTaskRunnerMaxAliveTime($payload['taskRunnerMaxAliveTime']);
        }

        if (isset($payload['maxTaskExecutionRetries'])) {
            $this->configService->setMaxTaskExecutionRetries($payload['maxTaskExecutionRetries']);
        }

        if (isset($payload['maxTaskInactivityPeriod'])) {
            $this->configService->setMaxTaskInactivityPeriod($payload['maxTaskInactivityPeriod']);
        }

        if (isset($payload['productSearchEndpointPassword'])) {
            $this->configService->setProductSearchEndpointPassword($payload['productSearchEndpointPassword']);
        }

        if (isset($payload['asyncProcessRequestTimeout'])) {
            $this->configService->setAsyncProcessRequestTimeout($payload['asyncProcessRequestTimeout']);
        }

        if (isset($payload['recipientsSynchronizationBatchSize'])) {
            $this->configService->setRecipientsSynchronizationBatchSize((int)$payload['recipientsSynchronizationBatchSize']);
        }

        if (isset($payload['sendWakeupSignal'])) {
            $this->taskRunnerWakeUp->wakeup();
        }

        return new JsonApiResponse(['message' => 'Successfully updated config values!']);
    }

    /**
     * Get last 10 queue items
     *
     * @return array
     * @throws QueueItemDeserializationException
     */
    private function getOldestQueuedItems(): array
    {
        $queueItemsMap = [];
        $queueItems = $this->taskQueueStorage->findOldestQueuedItems();
        foreach ($queueItems as $queueItem) {
            $queueItemsMap[] = $this->taskQueueStorage->toArray($queueItem);
        }

        return $queueItemsMap;
    }

    /**
     * Get last 10 queue items
     *
     * @return array
     * @throws QueueItemDeserializationException
     */
    private function getAllQueueItems(): array
    {
        $queueItemsMap = [];
        $queueItems = $this->taskQueueStorage->findAll();
        foreach ($queueItems as $queueItem) {
            $queueItemsMap[] = $this->taskQueueStorage->toArray($queueItem);
        }

        return $queueItemsMap;
    }
}
