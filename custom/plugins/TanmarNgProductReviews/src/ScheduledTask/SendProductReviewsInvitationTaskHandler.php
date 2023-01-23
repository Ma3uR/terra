<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\ScheduledTask;

use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Tanmar\ProductReviews\Components\MailHelper;
use Tanmar\ProductReviews\Components\LoggerHelper;
use Tanmar\ProductReviews\Service\ConfigService;

class SendProductReviewsInvitationTaskHandler extends ScheduledTaskHandler {

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelRepository;

    /**
     * @var MailHelper
     */
    protected $mailHelper;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var LoggerHelper
     */
    protected $loggerHelper;

    /**
     *
     * @param EntityRepositoryInterface $scheduledTaskRepository
     * @param EntityRepositoryInterface $salesChannelRepository
     * @param MailHelper $mailHelper
     * @param ConfigService $configService
     * @param LoggerHelper $loggerHelper
     */
    public function __construct(EntityRepositoryInterface $scheduledTaskRepository, EntityRepositoryInterface $salesChannelRepository, MailHelper $mailHelper, ConfigService $configService, LoggerHelper $loggerHelper) {
        parent::__construct($scheduledTaskRepository);
        $this->salesChannelRepository = $salesChannelRepository;
        $this->mailHelper = $mailHelper;
        $this->loggerHelper = $loggerHelper;
        $this->configService = $configService;
    }

    /**
     *
     * @return iterable
     */
    public static function getHandledMessages(): iterable {
        return [SendProductReviewsInvitationTask::class];
    }

    /**
     *
     * @return void
     */
    public function run(): void {
        $cronContext = Context::createDefaultContext();

        $oridinalConfig = $this->mailHelper->getConfig();

        $this->log('Schedules Task start');

        /** @var \Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult $salesChannels */
        $salesChannels = $this->salesChannelRepository->search(new Criteria(), $cronContext);

        $this->log(count($salesChannels) . ' SalesChannels');
        /** @var \Shopware\Core\System\SalesChannel\SalesChannelEntity $salesChannel */
        foreach ($salesChannels as $salesChannel) {
            $salesChannelOrdersSent = 0;
            $this->mailHelper->setConfig($this->configService->getSalesChannelConfig($salesChannel->getId()));
            if (!$this->mailHelper->getConfig()->isActive()) {
                continue;
            }
            $this->log('Checking orders for ' . $salesChannel->getName());

            /** @var \Shopware\Core\Checkout\Order\OrderEntity[] $orders */
            $orders = $this->mailHelper->getInvitationOrders($cronContext, $salesChannel)->getEntities();
            $this->log(count($orders) . ' orders found');
            foreach ($orders as $order) {
                if ($this->mailHelper->sendInvitationMail($cronContext, $order)) {
                    $this->log($order->getAutoIncrement() . ' invitation sent', ['id' => $order->getId()]);
                    $salesChannelOrdersSent++;
                } else {
                    $this->log('Failed to send invitation for ' . $order->getOrderNumber(), ['id' => $order->getId()], Logger::ERROR);
                }
            }
            $this->log($salesChannelOrdersSent . ' invitations sent in SalesChannel ' . $salesChannel->getName(), [], Logger::INFO);
        }
        $this->mailHelper->setConfig($oridinalConfig);
        $this->log('Schedules Task done');
    }

    protected function log(string $text, array $data = [], int $logLevel = Logger::DEBUG) {
        $this->loggerHelper->addDirectRecord(
                $logLevel,
                $text,
                $data
        );
    }

}
