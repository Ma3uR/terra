<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Recipients;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DashboardController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class DashboardController extends AbstractController
{
    public const CLEVERREACH_BUILD_EMAIL_URL = '/admin/mailing_create_new.php';

    /**
     * @var Queue
     */
    private $queueService;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var Recipients
     */
    private $recipientService;

    /**
     * SupportController constructor.
     *
     * @param Queue $queueService
     * @param Configuration $configService
     * @param Recipients $recipientService
     * @param Initializer $initializer
     */
    public function __construct(
        Queue $queueService,
        Configuration $configService,
        Recipients $recipientService,
        Initializer $initializer
    ) {
        $initializer->registerServices();
        $this->queueService = $queueService;
        $this->configService = $configService;
        $this->recipientService = $recipientService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/dashboard", name="api.cleverreach.dashboard", methods={"GET", "POST"})
     *
     * @param Context $context
     *
     * @return JsonApiResponse
     */
    public function getDashboardConfig(Context $context): JsonApiResponse
    {
        $this->configService->setShopwareContext($context);

        TaskQueue::wakeup();
        $isInitialSyncFailed = false;
        $errorDescription = '';
        $initialSync = $this->getInitialSync();
        if ($initialSync) {
            $isInitialSyncFailed = $initialSync->getStatus() === QueueItem::FAILED;
            $errorDescription = $initialSync->getFailureDescription();
        }

        $accountInfo = $this->configService->getUserInfo();
        $customerId = $accountInfo['id'] ?? '';
        $data = [
            'customerId' => $customerId,
            'isFirstEmailBuilt' => $this->configService->isFirstEmailBuilt(),
            'isImportStatisticDisplayed' => $this->configService->isImportStatisticsDisplayed(),
            'isInitialSyncFailed' => $isInitialSyncFailed,
            'errorDescription' => $errorDescription,
            'integrationListName' => $this->configService->getIntegrationListName(),
            'numberOfSyncedRecipients' => $this->configService->getNumberOfSyncedRecipients(),
            'segments' => $this->getSegments(),
            'emailUrl' => SingleSignOnProvider::getUrl(static::CLEVERREACH_BUILD_EMAIL_URL),
        ];

        $this->configService->setImportStatisticsDisplayed(true);

        return new JsonApiResponse($data);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/dashboard/setEmailBuilt", name="api.cleverreach.dashboard.setEmailBuild", defaults={"auth_required"=false}, methods={"GET", "POST"})
     */
    public function setFirstEmailBuilt(): JsonApiResponse
    {
        $this->configService->setIsFirstEmailBuilt(true);

        return new JsonApiResponse(['success' => true]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/dashboard/retry", name="api.cleverreach.dashboard.retry",
     *     methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     *
     * @throws QueueStorageUnavailableException
     */
    public function enqueueInitialSync(): JsonApiResponse
    {
        $this->queueService->enqueue($this->configService->getQueueName(), new InitialSyncTask());

        return new JsonApiResponse(['success' => true]);
    }

    /**
     * Get tags as array of strings
     *
     * @return array
     */
    private function getSegments(): array
    {
        $tags = $this->recipientService->getAllTags()->getTags();

        return array_map(function ($tag) {
            /** @var Tag $tag */
            return $tag->getTitle();
        }, $tags);
    }

    /**
     * @return QueueItem|null
     */
    private function getInitialSync(): ?QueueItem
    {
        return $this->queueService->findLatestByType(InitialSyncTask::getClassName());
    }
}
