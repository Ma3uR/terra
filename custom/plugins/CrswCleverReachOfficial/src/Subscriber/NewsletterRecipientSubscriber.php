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
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Shopware\Core\Content\Newsletter\NewsletterEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NewsletterRecipientSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber
 */
class NewsletterRecipientSubscriber implements EventSubscriberInterface
{
    /**
     * @var NewsletterRecipientRepository
     */
    private $newsletterRecipientRepository;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * Emails stored before newsletter recipient is deleted/changed
     *
     * @var array
     */
    private static $previousEmails = [];

    /**
     * Emails that have been changed on newsletter recipient update.
     *
     * @var array
     */
    private static $newEmails = [];

    /**
     * CustomerSubscriber constructor.
     *
     * @param Initializer $initializer
     * @param NewsletterRecipientRepository $newsletterRecipientRepository
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Initializer $initializer,
        NewsletterRecipientRepository $newsletterRecipientRepository,
        CustomerRepository $customerRepository
    ) {
        $initializer->registerServices();
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Returns subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NewsletterEvents::NEWSLETTER_CONFIRM_EVENT => 'onNewsletterConfirm',
            NewsletterEvents::NEWSLETTER_RECIPIENT_WRITTEN_EVENT => 'onNewsletterSave',
            NewsletterEvents::NEWSLETTER_RECIPIENT_DELETED_EVENT => 'onNewsletterDelete',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
            'newsletter_recipient_tag.deleted' => 'onNewsletterTagDelete',
        ];
    }

    /**
     * Handles newsletter tag deleted event
     *
     * @param EntityDeletedEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    public function onNewsletterTagDelete(EntityDeletedEvent $event): void
    {
        $this->getConfigService()->setShopwareContext($event->getContext());

        $newsletterIds = [];
        $payloads = $event->getPayloads();
        foreach ($payloads as $payload) {
            if (array_key_exists('newsletterRecipientId', $payload)) {
                $newsletterIds[] = $payload['newsletterRecipientId'];
            }
        }

        $this->updateRecipients($newsletterIds);
    }

    /**
     * @param NewsletterConfirmEvent $newsletterConfirmEvent
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    public function onNewsletterConfirm(NewsletterConfirmEvent $newsletterConfirmEvent): void
    {
        $this->getConfigService()->setShopwareContext($newsletterConfirmEvent->getContext());

        $recipient = $newsletterConfirmEvent->getNewsletterRecipient();
        $this->synchronizeRecipient($recipient);
    }

    /**
     * Handles newsletter save event
     *
     * @param EntityWrittenEvent $event
     *
     * @throws QueueStorageUnavailableException
     * @throws InconsistentCriteriaIdsException
     */
    public function onNewsletterSave(EntityWrittenEvent $event): void
    {
        $this->getConfigService()->setShopwareContext($event->getContext());

        $writeResults = $event->getWriteResults();
        foreach ($writeResults as $writeResult) {
            $payload = $writeResult->getPayload();
            if (array_key_exists('email', $payload)) {
                self::$newEmails[$payload['id']] = $payload['email'];
            }
        }

        $sourceIds = $event->getIds();
        $this->deactivateOldEmails($sourceIds);
        $this->updateRecipients($sourceIds);
    }

    /**
     * Handles newsletter recipient deleted
     *
     * @param EntityDeletedEvent $deletedEvent
     *
     * @throws QueueStorageUnavailableException
     */
    public function onNewsletterDelete(EntityDeletedEvent $deletedEvent): void
    {
        $this->getConfigService()->setShopwareContext($deletedEvent->getContext());

        if (!empty(static::$previousEmails)) {
            $ids = json_encode($deletedEvent->getIds());
            Logger::logInfo("Customer delete event detected. Customer ids: {$ids}", 'Integration');
            TaskQueue::enqueue(new FilterSyncTask());
            TaskQueue::enqueue(new RecipientDeactivateSyncTask(static::$previousEmails));
            static::$previousEmails = [];
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
        if (in_array($request->get('_route'), ['api.newsletter_recipient.delete', 'api.newsletter_recipient.update'], true)) {
            $path = $request->get('path');
            // check if route contains subpaths
            if (!strpos($path, '/')) {
                $this->saveEmailForDelete($path);
            }
        }
    }

    /**
     * @param string|null $id
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function saveEmailForDelete(?string $id): void
    {
        if ($id) {
            $newsletterRecipient = $this->newsletterRecipientRepository->getNewsletterSubscriberById(
                $id,
                $this->getConfigService()->getShopwareContext()
            );
            if ($newsletterRecipient) {
                static::$previousEmails[$id] = $newsletterRecipient->getEmail();
            }
        }
    }

    /**
     * Check if email changed and deactivates recipients with old email address
     *
     * @param array $sourceIds
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    private function deactivateOldEmails(array $sourceIds): void
    {
        $emailsForDeactivation = [];
        foreach ($sourceIds as $id) {
            if ($this->isEmailChanged($id)) {
                $emailsForDeactivation[] = static::$previousEmails[$id];
            }
        }

        static::$previousEmails = [];
        static::$newEmails = [];
        if (!empty($emailsForDeactivation)) {
            TaskQueue::enqueue(new RecipientDeactivateSyncTask($emailsForDeactivation));
        }
    }

    /**
     * Check if newsletter recipient email changed
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
     * Check if customer with same email exists and synchronize recipient
     *
     * @param NewsletterRecipientEntity $recipient
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    private function synchronizeRecipient(NewsletterRecipientEntity $recipient): void
    {
        $customer = $this->customerRepository->getCustomerByEmail(
            $recipient->getEmail(),
            $this->getConfigService()->getShopwareContext()
        );
        $idForSync = $customer ?
            RecipientService::CUSTOMER_PREFIX . $customer->getId()
            : RecipientService::SUBSCRIBER_PREFIX . $recipient->getId();
        Logger::logInfo('Newsletter recipient register event detected: Recipient email: ' . $recipient->getEmail(), 'Integration');
        TaskQueue::enqueue(new RecipientSyncTask([$idForSync]));
        TaskQueue::enqueue(new FilterSyncTask());
    }

    /**
     * @param array $sourceIds
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    private function updateRecipients(array $sourceIds): void
    {
        $customerIds = [];
        $newsletterIds = [];
        $newsletterRecipients = $this->newsletterRecipientRepository->getNewsletterSubscribers(
            $sourceIds,
            $this->getConfigService()->getShopwareContext()
        );
        /** @var NewsletterRecipientEntity $newsletterRecipientEntity */
        foreach ($newsletterRecipients as $newsletterRecipientEntity) {
            $customer = $this->customerRepository->getCustomerByEmail(
                $newsletterRecipientEntity->getEmail(),
                $this->getConfigService()->getShopwareContext()
            );
            if ($customer) {
                $customerIds[] = RecipientService::CUSTOMER_PREFIX . $customer->getId();
            } else {
                $newsletterIds[] = RecipientService::SUBSCRIBER_PREFIX . $newsletterRecipientEntity->getId();
            }
        }
        Logger::logInfo('Newsletter recipient update event detected: Recipient ids: ' . json_encode($sourceIds), 'Integration');

        if (!empty($customerIds)) {
            TaskQueue::enqueue(new RecipientSyncTask($customerIds));
        }

        if (!empty($newsletterIds)) {
            TaskQueue::enqueue(new RecipientSyncTask($newsletterIds));
        }

        TaskQueue::enqueue(new FilterSyncTask());
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
