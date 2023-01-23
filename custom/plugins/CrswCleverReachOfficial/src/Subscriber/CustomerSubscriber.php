<?php

namespace Crsw\CleverReachOfficial\Subscriber;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\FilterSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RecipientDeactivateSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RecipientSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Entity\Customer\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\NewsletterRecipient\NewsletterRecipientRepository;
use Crsw\CleverReachOfficial\Service\Business\RecipientService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CustomerSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber
 */
class CustomerSubscriber implements EventSubscriberInterface
{
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
     * Emails stored before customer is deleted/changed
     *
     * @var array
     */
    private static $previousEmails = [];

    /**
     * Emails that have been changed on customer update.
     *
     * @var array
     */
    private static $newEmails = [];

    /**
     * CustomerSubscriber constructor.
     *
     * @param Initializer $initializer
     * @param CustomerRepository $customerRepository
     * @param NewsletterRecipientRepository $newsletterRecipientRepository
     * @param RecipientService $recipientService
     */
    public function __construct(
        Initializer $initializer,
        CustomerRepository $customerRepository,
        NewsletterRecipientRepository $newsletterRecipientRepository,
        RecipientService $recipientService
    ) {
        $initializer->registerServices();
        $this->customerRepository = $customerRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->recipientService = $recipientService;
    }


    /**
     * Returns subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_REGISTER_EVENT => 'onCustomerRegister',
            CustomerRegisterEvent::class => 'onCustomerRegister',
            CustomerEvents::CUSTOMER_DELETED_EVENT => 'onCustomerDelete',
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerSave',
            CustomerEvents::CUSTOMER_ADDRESS_WRITTEN_EVENT => 'onCustomerAddressSave',
            'customer_tag.deleted' => 'onCustomerTagDelete',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
        ];
    }

    /**
     * Handles customer tag deleted event
     *
     * @param EntityDeletedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public function onCustomerTagDelete(EntityDeletedEvent $event): void
    {
        $this->getConfigService()->setShopwareContext($event->getContext());

        $idsForSync = [];
        $writeResults = $event->getWriteResults();
        foreach ($writeResults as $writeResult) {
            $payload = $writeResult->getPayload();
            if (array_key_exists('customerId', $payload)) {
                $idsForSync[] = RecipientService::CUSTOMER_PREFIX . $payload['customerId'];
            }
        }

        Logger::logInfo('Customer tag delete event detected.', 'Integration');
        TaskQueue::enqueue(new FilterSyncTask());
        TaskQueue::enqueue(new RecipientSyncTask($idsForSync));
    }

    /**
     * Handles customer address change
     *
     * @param EntityWrittenEvent $addressSaveEvent
     *
     * @throws QueueStorageUnavailableException
     */
    public function onCustomerAddressSave(EntityWrittenEvent $addressSaveEvent): void
    {
        $this->getConfigService()->setShopwareContext($addressSaveEvent->getContext());

        $customerIds = [];
        $writeResults = $addressSaveEvent->getWriteResults();
        /** @var EntityWriteResult $entityWriteResult */
        foreach ($writeResults as $entityWriteResult) {
            $payload = $entityWriteResult->getPayload();
            if (array_key_exists('customerId', $payload)) {
                $customerIds[] = $payload['customerId'];
            }
        }

        Logger::logInfo('Customer address change event detected', 'Integration');
        $idsForSync = $this->recipientService->appendCustomerPrefix($customerIds);

        TaskQueue::enqueue(new RecipientSyncTask($idsForSync));
    }

    /**
     * Handles customer register event
     *
     * @param CustomerRegisterEvent $customerRegisterEvent
     *
     * @throws QueueStorageUnavailableException
     */
    public function onCustomerRegister(CustomerRegisterEvent $customerRegisterEvent): void
    {
        $this->getConfigService()->setShopwareContext($customerRegisterEvent->getContext());

        $customer = $customerRegisterEvent->getCustomer();
        Logger::logInfo("Customer delete event detected. Customer email: {$customer->getEmail()}", 'Integration');
        TaskQueue::enqueue(new RecipientSyncTask([RecipientService::CUSTOMER_PREFIX . $customer->getId()]));
    }

    /**
     * Handles customer save event
     *
     * @param EntityWrittenEvent $entityWrittenEvent
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    public function onCustomerSave(EntityWrittenEvent $entityWrittenEvent): void
    {
        $this->getConfigService()->setShopwareContext($entityWrittenEvent->getContext());

        $writeResults = $entityWrittenEvent->getWriteResults();
        foreach ($writeResults as $writeResult) {
            $payload = $writeResult->getPayload();
            if (array_key_exists('email', $payload)) {
                self::$newEmails[$payload['id']] = $payload['email'];
            }
        }

        $sourceIds = $entityWrittenEvent->getIds();
        $newsletterIds = $this->deactivateOldEmailsAndReturnsNewsletterIdsForSync($sourceIds);
        $ids = $this->recipientService->appendCustomerPrefix($sourceIds);
        $ids = array_merge($ids, $newsletterIds);
        $jsonIds = json_encode($ids);
        Logger::logInfo("Customer save event detected. Customer ids: {$jsonIds}", 'Integration');
        TaskQueue::enqueue(new FilterSyncTask());
        TaskQueue::enqueue(new RecipientSyncTask($ids));
    }

    /**
     * Handles customer delete event
     *
     * @param EntityDeletedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public function onCustomerDelete(EntityDeletedEvent $event): void
    {
        $this->getConfigService()->setShopwareContext($event->getContext());

        if (!empty(static::$previousEmails)) {
            $ids = json_encode($event->getIds());
            Logger::logInfo("Customer delete event detected. Customer ids: {$ids}", 'Integration');
            TaskQueue::enqueue(new RecipientDeactivateSyncTask(static::$previousEmails));
            TaskQueue::enqueue(new FilterSyncTask());
            static::$previousEmails = [];
        }
    }

    /**
     * Saves data for delete
     *
     * @param ControllerEvent $controllerEvent
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function saveDataForDelete(ControllerEvent $controllerEvent): void
    {
        $this->getConfigService()->setShopwareContext(Context::createDefaultContext());

        $request = $controllerEvent->getRequest();
        $routeName = $request->get('_route');
        if (in_array($routeName, ['api.customer.delete', 'api.customer.update'])) {
            $path = $request->get('path');
            // check if route contains subpaths
            if (!strpos($path, '/')) {
                $this->savePreviousEmail($path);
            }
        } elseif ($routeName === 'frontend.account.profile.email.save') {
            /** @var SalesChannelContext $salesChannelContext */
            $salesChannelContext = $request->get('sw-sales-channel-context');
            if ($salesChannelContext) {
                $customer = $salesChannelContext->getCustomer();
                if ($customer) {
                    static::$previousEmails[$customer->getId()] = $customer->getEmail();
                }
            }
        }
    }

    /**
     * Saves previous email
     *
     * @param string|null $id
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function savePreviousEmail(?string $id): void
    {
        if ($id) {
            $customer = $this->customerRepository->getCustomerById(
                $id,
                $this->getConfigService()->getShopwareContext()
            );
            if ($customer) {
                static::$previousEmails[$id] = $customer->getEmail();
            }
        }
    }

    /**
     * Check if email changed and deactivates recipients with old email address
     *
     * @param array $sourceIds
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    private function deactivateOldEmailsAndReturnsNewsletterIdsForSync(array $sourceIds): array
    {
        $emailsForDeactivation = [];
        $newsletterIdsForSync = [];
        foreach ($sourceIds as $id) {
            if ($this->isEmailChanged($id)) {
                // Check if newsletter entity with old email exists.
                $newsletterRecipient = $this->newsletterRecipientRepository->getNewsletterSubscriberByEmail(
                    static::$previousEmails[$id],
                    $this->getConfigService()->getShopwareContext()
                );
                if (!$newsletterRecipient) {
                    $emailsForDeactivation[] = static::$previousEmails[$id];
                } else {
                    $newsletterIdsForSync[] = RecipientService::SUBSCRIBER_PREFIX . $newsletterRecipient->getId();
                }
            }
        }

        static::$previousEmails = [];
        static::$newEmails = [];
        if (!empty($emailsForDeactivation)) {
            TaskQueue::enqueue(new RecipientDeactivateSyncTask($emailsForDeactivation));
        }

        return $newsletterIdsForSync;
    }

    /**
     * Check if customer email changed
     *
     * @param string $id
     *
     * @return bool
     */
    private function isEmailChanged(string $id): bool
    {
        return !empty(self::$previousEmails)
            && !empty(self::$newEmails)
            && self::$previousEmails[$id] !== self::$newEmails[$id];
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
