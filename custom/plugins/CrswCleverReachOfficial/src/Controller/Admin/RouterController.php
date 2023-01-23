<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\AuthProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RouterController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class RouterController extends AbstractController
{
    public const WELCOME_STATE_CODE = 'welcome';
    public const INITIAL_SYNC_STATE_CODE = 'initialSync';
    public const DASHBOARD_STATE_CODE = 'dashboard';
    public const REFRESH_STATE_CODE = 'refresh';

    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var Queue
     */
    private $queueService;
    /**
     * @var AuthProxy
     */
    private $authProxy;

    /**
     * RouterController constructor.
     *
     * @param Configuration $configService
     * @param Queue $queueService
     * @param AuthProxy $authProxy
     * @param Initializer $initializer
     */
    public function __construct(
        Configuration $configService,
        Queue $queueService,
        AuthProxy $authProxy,
        Initializer $initializer
    ) {
        $initializer->registerServices();
        $this->configService = $configService;
        $this->queueService = $queueService;
        $this->authProxy = $authProxy;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/router", name="api.cleverreach.router", methods={"GET", "POST"})
     *
     * @param Context $context
     *
     * @return JsonApiResponse
     */
    public function getRouteName(Context $context): JsonApiResponse
    {
        $this->configService->setShopwareContext($context);

        if (!$this->isAuthTokenValid()) {
            $page = static::WELCOME_STATE_CODE;
        } else if ($this->isInitialSyncInProgress()) {
            $page = static::INITIAL_SYNC_STATE_CODE;
        } else if (!$this->isRefreshTokenValid() || !$this->authProxy->isConnected()) {
            $page = static::REFRESH_STATE_CODE;
        } else {
            $page = static::DASHBOARD_STATE_CODE;
        }

        return new JsonApiResponse(['routeName' => $page]);
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function isAuthTokenValid(): bool
    {
        $accessToken = $this->configService->getAccessToken();

        return !empty($accessToken);
    }

    /**
     * @return bool
     */
    private function isRefreshTokenValid(): bool
    {
        $refreshToken = $this->configService->getRefreshToken();

        return !empty($refreshToken);
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function isInitialSyncInProgress(): bool
    {
        /** @var QueueItem $initialSyncTaskItem */
        $initialSyncTaskItem = $this->queueService->findLatestByType('InitialSyncTask');
        if (!$initialSyncTaskItem) {
            try {
                $this->queueService->enqueue($this->configService->getQueueName(), new InitialSyncTask());
            } catch (QueueStorageUnavailableException $e) {
                // If task enqueue fails do nothing but report that initial sync is in progress
            }

            return true;
        }

        return $initialSyncTaskItem->getStatus() !== QueueItem::COMPLETED
            && $initialSyncTaskItem->getStatus() !== QueueItem::FAILED;
    }
}
