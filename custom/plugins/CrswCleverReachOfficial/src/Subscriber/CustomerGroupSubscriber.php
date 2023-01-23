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
use Crsw\CleverReachOfficial\Entity\CustomerGroup\CustomerGroupRepository;
use Crsw\CleverReachOfficial\Service\Business\RecipientService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CustomerGroupSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber
 */
class CustomerGroupSubscriber implements EventSubscriberInterface
{

    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;
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
    private static $groupsForDelete = [];

    /**
     * CustomerGroupSubscriber constructor.
     *
     * @param CustomerRepository $customerRepository
     * @param CustomerGroupRepository $customerGroupRepository
     * @param Recipients $recipientService
     * @param Initializer $initializer
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CustomerGroupRepository $customerGroupRepository,
        Recipients $recipientService,
        Initializer $initializer
    ) {
        $initializer->registerServices();
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->recipientService = $recipientService;
    }


    public static function getSubscribedEvents()
    {
        return [
            // this event triggers when new group is created or modified
            CustomerEvents::CUSTOMER_GROUP_WRITTEN_EVENT => 'onCustomerGroupChange',
            CustomerEvents::CUSTOMER_GROUP_DELETED_EVENT => 'onCustomerGroupDelete',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
        ];
    }

    /**
     * @param EntityWrittenEvent $event
     *
     * @throws QueueStorageUnavailableException
     * @throws InconsistentCriteriaIdsException
     */
    public function onCustomerGroupChange(EntityWrittenEvent $event): void
    {
        $this->getConfigService()->setContext($event->getContext());

        $ids = json_encode($event->getIds());
        Logger::logInfo("Sales channel edit event detected. Sales Channel id: {$ids}", 'Integration');
        TaskQueue::enqueue(new FilterSyncTask());
        foreach ($event->getIds() as $customerGroupId) {
            if (!empty(static::$groupsForDelete[$customerGroupId])) {
                $this->synchronizeRecipientFromGroup($customerGroupId);
            }
        }
    }

    /**
     * @param $event
     *
     * @throws QueueStorageUnavailableException
     */
    public function onCustomerGroupDelete(EntityDeletedEvent $event): void
    {
        $this->getConfigService()->setContext($event->getContext());

        $ids = json_encode($event->getIds());
        Logger::logInfo("Customer group delete event detected. Customer group id: {$ids}", 'Integration');
        TaskQueue::enqueue(new FilterSyncTask());
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
        if ($request->get('_route') === 'api.customer_group.update') {
            $groupId = $request->get('path');
            // check if route contains subpaths
            if (!strpos($groupId, '/')) {
                $this->saveOldGroupName($groupId);
            }
        }
    }

    /**
     * @param string $groupId
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function saveOldGroupName(string $groupId): void
    {
        $customerGroup = $this->customerGroupRepository->getCustomerGroupById(
            $groupId,
            $this->getConfigService()->getShopwareContext()
        );
        if ($customerGroup) {
            static::$groupsForDelete[$groupId] = $customerGroup->getName();
        }
    }

    /**
     * @param $customerGroupId
     *
     * @throws InconsistentCriteriaIdsException
     * @throws QueueStorageUnavailableException
     */
    private function synchronizeRecipientFromGroup($customerGroupId): void
    {
        $customers = $this->customerRepository->getCustomersByCustomerGroup(
            $customerGroupId,
            $this->getConfigService()->getShopwareContext()
        );
        $customerIds = $customers->getIds();
        if (!empty($customerIds)) {
            $idsForSync = $this->recipientService->appendCustomerPrefix($customerIds);
            $tagsForDelete = new TagCollection([new Tag(static::$groupsForDelete[$customerGroupId], RecipientService::GROUP_TYPE)]);
            TaskQueue::enqueue(new RecipientSyncTask($idsForSync, $tagsForDelete));
        }

        unset(static::$groupsForDelete[$customerGroupId]);
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
