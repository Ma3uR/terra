<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RefreshUserInfoTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
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
 * Class CheckConnectionStatusController
 *
 * @package Shopware\Controller\Admin
 */
class CheckConnectionStatusController extends AbstractController
{
    /**
     * @var Queue
     */
    private $queueService;

    /**
     * CheckConnectionStatusController constructor.
     *
     * @param Queue $queueService
     * @param Initializer $initializer
     */
    public function __construct(Queue $queueService, Initializer $initializer)
    {
        $initializer->registerServices();
        $this->queueService = $queueService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/status", name="api.cleverreach.status", defaults={"auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Context $context
     *
     * @return JsonApiResponse
     */
    public function checkConnectionStatus(Context $context): JsonApiResponse
    {
        /** @var ConfigService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setShopwareContext($context);

        $status = 'finished';
        $queueItem = $this->queueService->findLatestByType(RefreshUserInfoTask::getClassName());

        if ($queueItem !== null) {
            $queueStatus = $queueItem->getStatus();
            if ($queueStatus !== QueueItem::FAILED && $queueStatus !== QueueItem::COMPLETED) {
                $status = QueueItem::IN_PROGRESS;
            }
        }

        return new JsonApiResponse(['status' => $status]);
    }
}
