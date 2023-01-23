<?php

namespace Crsw\CleverReachOfficial\Subscriber;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\TagCollection;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Recipients;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\FilterSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RecipientSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Entity\Customer\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\NewsletterRecipient\NewsletterRecipientRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\SalesChannelRepository;
use Crsw\CleverReachOfficial\Service\Business\RecipientService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SalesChannelSubscriber implements EventSubscriberInterface
{
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var NewsletterRecipientRepository
     */
    private $newsletterRecipientRepository;
    /**
     * @var RecipientService
     */
    private $recipientService;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var array
     */
    private static $salesChannelForDelete = [];

    /**
     * SalesChannelSubscriber constructor.
     *
     * @param SalesChannelRepository $salesChannelRepository
     * @param CustomerRepository $customerRepository
     * @param NewsletterRecipientRepository $newsletterRecipientRepository
     * @param Recipients $recipients
     * @param Initializer $initializer
     */
    public function __construct(
        SalesChannelRepository $salesChannelRepository,
        CustomerRepository $customerRepository,
        NewsletterRecipientRepository $newsletterRecipientRepository,
        Recipients $recipients,
        Initializer $initializer
    ) {
        $initializer->registerServices();
        $this->salesChannelRepository = $salesChannelRepository;
        $this->customerRepository = $customerRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->recipientService = $recipients;
    }


    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'onSalesChannelDelete',
            SalesChannelEvents::SALES_CHANNEL_WRITTEN => 'onSalesChannelSave',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
            KernelEvents::RESPONSE => ['onIframeResponse', -1]
        ];
    }

    /**
     * @param ResponseEvent $event
     */
    public function onIframeResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set(PlatformRequest::HEADER_FRAME_OPTIONS, 'allowall');
    }

    /**
     * @param EntityDeletedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public function onSalesChannelDelete(EntityDeletedEvent $event): void
    {
        $this->getConfigService()->setShopwareContext($event->getContext());

        $ids = json_encode($event->getIds());
        Logger::logInfo("Sales channel delete event detected. Sales Channel ids: {$ids}", 'Integration');
        TaskQueue::enqueue(new FilterSyncTask());
    }

    /**
     * @param EntityWrittenEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    public function onSalesChannelSave(EntityWrittenEvent $event): void
    {
        $this->getConfigService()->setShopwareContext($event->getContext());

        $ids = json_encode($event->getIds());
        Logger::logInfo("Sales channel edit event detected. Sales Channel ids: {$ids}", 'Integration');
        TaskQueue::enqueue(new FilterSyncTask());
        foreach ($event->getIds() as $salesChannelId) {
            if (!empty(static::$salesChannelForDelete[$salesChannelId])) {
                $this->synchronizeRecipientFromSalesChannel($salesChannelId);
            }
        }
    }

    /**
     * @param ControllerEvent $controllerEvent
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function saveDataForDelete(ControllerEvent $controllerEvent): void
    {
        $this->getConfigService()->setShopwareContext(Context::createDefaultContext());

        $request = $controllerEvent->getRequest();
        $routeName = $request->get('_route');
        if (in_array($routeName, ['api.sales_channel.update', 'api.sales_channel.delete'], true)) {
            $salesChannelId = $request->get('path');
            // check if route contains subpaths
            if (!strpos($salesChannelId, '/')) {
                $this->saveSalesChannelName($salesChannelId);
            }
        }
    }

    /**
     * @param string $salesChannelId
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    private function synchronizeRecipientFromSalesChannel(string $salesChannelId): void
    {
        $customerCollection = $this->customerRepository->getCustomersBySalesChannelId(
            $salesChannelId,
            $this->getConfigService()->getShopwareContext()
        );
        $customerEmails = $customerCollection->map(function (CustomerEntity $customerEntity) {
            return $customerEntity->getEmail();
        });

        $newsletterSubscribers = $this->newsletterRecipientRepository->getNewsletterSubscriberBySalesChannelId(
            $salesChannelId,
            $customerEmails,
            $this->getConfigService()->getShopwareContext()
        );
        $newsletterIds = $newsletterSubscribers->getIds();
        $idsForSync = array_merge(
            $this->recipientService->appendCustomerPrefix($customerCollection->getIds()),
            $this->recipientService->appendSubscriberPrefix($newsletterIds)
        );

        if (!empty($idsForSync)) {
            $tagsForDelete = new TagCollection([new Tag(static::$salesChannelForDelete[$salesChannelId], RecipientService::STORE_TYPE)]);
            TaskQueue::enqueue(new RecipientSyncTask($idsForSync, $tagsForDelete));
        }

        unset(static::$salesChannelForDelete[$salesChannelId]);
    }

    /**
     * @param string $salesChannelId
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function saveSalesChannelName(string $salesChannelId): void
    {
        $salesChannel = $this->salesChannelRepository->getSalesChannelById(
            $salesChannelId,
            $this->getConfigService()->getShopwareContext()
        );

        if ($salesChannel) {
            static::$salesChannelForDelete[$salesChannelId] = $salesChannel->getName();
        }
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
