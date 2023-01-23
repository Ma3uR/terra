<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InitialSyncController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class InitialSyncController extends AbstractController
{
    /**
     * @var Queue
     */
    private $queueService;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * InitialSyncController constructor.
     *
     * @param Queue $queueService
     * @param Configuration $configuration
     * @param Initializer $initializer
     */
    public function __construct(Queue $queueService, Configuration $configuration, Initializer $initializer)
    {
        $initializer->registerServices();
        $this->queueService = $queueService;
        $this->configuration = $configuration;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/initialSync/config", name="api.cleverreach.initialSync.config", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getInitialSyncConfig(Context $context): JsonApiResponse
    {
        $this->getConfigService()->setShopwareContext($context);

        return new JsonApiResponse([
            'emailUrl' => SingleSignOnProvider::getUrl(DashboardController::CLEVERREACH_BUILD_EMAIL_URL),
        ]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/initialSync", name="api.cleverreach.initialSync", methods={"GET", "POST"})
     *
     * @param Context $context
     *
     * @return JsonApiResponse
     *
     * @throws QueueItemDeserializationException
     */
    public function getInitialSyncStatus(Context $context): JsonApiResponse
    {
        $this->getConfigService()->setShopwareContext($context);

        /** @var QueueItem $queueItem */
        $queueItem = $this->queueService->findLatestByType(InitialSyncTask::getClassName());
        if ($queueItem === null) {
            return new JsonApiResponse([
                'status' => QueueItem::FAILED,
            ]);
        }

        /** @var InitialSyncTask $initialSyncTask */
        $initialSyncTask = $queueItem->getTask();
        $initialSyncTaskProgress = $initialSyncTask->getProgressByTask();

        return new JsonApiResponse([
            'integrationList' => $this->configuration->getIntegrationListName(),
            'status' => $queueItem->getStatus(),
            'taskStatuses' => [
                'subscriberList' => [
                    'status' => $this->getStatus((int)$initialSyncTaskProgress['subscriberList']),
                    'progress' => (int)$initialSyncTaskProgress['subscriberList'],
                ],
                'addFields' => [
                    'status' => $this->getStatus((int)$initialSyncTaskProgress['fields']),
                    'progress' => (int)$initialSyncTaskProgress['fields'],
                ],
                'recipientSync' => [
                    'status' => $this->getStatus((int)$initialSyncTaskProgress['recipients']),
                    'progress' => (int)$initialSyncTaskProgress['recipients'],
                ],
            ],
        ]);
    }

    /**
     * @param int $progress
     *
     * @return string
     */
    private function getStatus(int $progress): string
    {
        $status = QueueItem::QUEUED;
        if (0 < $progress && $progress < 100) {
            $status = QueueItem::IN_PROGRESS;
        } else if ($progress >= 100) {
            $status = QueueItem::COMPLETED;
        }

        return $status;
    }

    /**
     * Returns an instance of configuration service.
     *
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
