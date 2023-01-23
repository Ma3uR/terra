<?php

namespace Crsw\CleverReachOfficial\Subscriber;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\CampaignOrderSync;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Entity\Order\OrderItemRepository;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class OrderSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber
 */
class OrderSubscriber implements EventSubscriberInterface
{
    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * OrderSubscriber constructor.
     *
     * @param OrderItemRepository $orderItemRepository
     * @param SessionInterface $session
     * @param Initializer $initializer
     */
    public function __construct(OrderItemRepository $orderItemRepository, SessionInterface $session, Initializer $initializer)
    {
        $initializer->registerServices();
        $this->orderItemRepository = $orderItemRepository;
        $this->session = $session;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderSave',
        ];
    }

    /**
     * @param EntityWrittenEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    public function onOrderSave(EntityWrittenEvent $event): void
    {
        $this->getConfigService()->setContext($event->getContext());

        $ids = $event->getIds();
        $orderId = reset($ids);

        Logger::logInfo("Order created event detected. Order id: {$orderId}", 'Integration');
        // there is no need for synchronizing recipient since customer.written event triggers before
        // order.written event
        TaskQueue::enqueue(new CampaignOrderSync($this->getOrderItemsIdsMap($orderId)));
    }

    /**
     * Returns map in format [itemId => crMailing]
     *
     * @param string $orderId
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function getOrderItemsIdsMap(string $orderId): array
    {
        $map = [];
        $crMailing = $this->session->get('crMailing');
        $orderItems = $this->orderItemRepository->getOrderItemsByOrderId(
            $orderId,
            $this->getConfigService()->getShopwareContext()
        );
        $itemIds = $orderItems->getIds();
        foreach ($itemIds as $itemId) {
            $map[$itemId] = $crMailing;
        }

        return $map;
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
