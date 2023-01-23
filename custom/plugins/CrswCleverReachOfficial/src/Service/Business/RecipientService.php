<?php

namespace Crsw\CleverReachOfficial\Service\Business;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Recipient;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\SpecialTag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\SpecialTagCollection;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\TagCollection;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\OrderItems;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Recipients;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\RecipientsGetException;
use Crsw\CleverReachOfficial\Entity\Customer\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\CustomerGroup\CustomerGroupRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\SalesChannelRepository;
use Crsw\CleverReachOfficial\Entity\Tag\TagRepository;
use Crsw\CleverReachOfficial\Entity\NewsletterRecipient\NewsletterRecipientRepository;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\Tag\TagEntity;

/**
 * Class RecipientService
 *
 * @package Crsw\CleverReachOfficial\Service\Business
 */
class RecipientService implements Recipients
{
    public const CUSTOMER_PREFIX = 'C-';
    public const SUBSCRIBER_PREFIX = 'S-';

    public const GROUP_TYPE = 'Group';
    public const TAG_TYPE = 'Tag';
    public const STORE_TYPE = 'Store';
    public const ACCOUNT_TYPE = 'AccountType';

    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var NewsletterRecipientRepository
     */
    private $newsletterRecipientRepository;
    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;
    /**
     * @var TagRepository
     */
    private $tagRepository;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * RecipientService constructor.
     *
     * @param CustomerRepository $customerRepository
     * @param NewsletterRecipientRepository $newsletterRecipientRepository
     * @param CustomerGroupRepository $customerGroupRepository
     * @param TagRepository $tagRepository
     * @param SalesChannelRepository $salesChannelRepository
     * @param OrderItems $orderService
     */
    public function __construct(
        CustomerRepository $customerRepository,
        NewsletterRecipientRepository $newsletterRecipientRepository,
        CustomerGroupRepository $customerGroupRepository,
        TagRepository $tagRepository,
        SalesChannelRepository $salesChannelRepository,
        OrderItems $orderService
    ) {
        $this->customerRepository = $customerRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->tagRepository = $tagRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->orderService = $orderService;
    }

    /**
     * Gets all tags as a collection.
     *
     * @return TagCollection
     *   Collection of integration tags.
     */
    public function getAllTags(): TagCollection
    {
        $collection = new TagCollection();
        $context = $this->getConfigService()->getShopwareContext();
        try {
            $collection->add($this->formatTags($this->customerGroupRepository->getCustomerGroups($context), self::GROUP_TYPE));
            $collection->add($this->formatTags($this->tagRepository->getTags($context), self::TAG_TYPE));
            $collection->add($this->formatTags($this->salesChannelRepository->getSalesChannels($context), self::STORE_TYPE));
        } catch (\Exception $exception) {
            Logger::logError("Failed to fetch all customer tags: {$exception->getMessage()}", 'Integration');
        }

        $collection->addTag(new Tag('Guest', self::ACCOUNT_TYPE));
        $collection->addTag(new Tag('Registered', self::ACCOUNT_TYPE));

        return $collection;
    }

    /**
     * Gets all special tags as a collection.
     *
     * @return SpecialTagCollection
     *   Collection of integration supported special tags.
     */
    public function getAllSpecialTags(): SpecialTagCollection
    {
        return new SpecialTagCollection([SpecialTag::customer(), SpecialTag::subscriber(), SpecialTag::buyer()]);
    }

    /**
     * Gets all recipients for passed batch IDs with tags.
     *
     * @param array $batchRecipientIds
     * @param bool $includeOrders
     *
     * @return Recipient[]
     * @throws \Exception
     */
    public function getRecipientsWithTags(array $batchRecipientIds, $includeOrders): array
    {
        $customersFormatted = [];
        $context = $this->getConfigService()->getShopwareContext();
        try {
            $batchIdsMap = $this->createBatchIdsMap($batchRecipientIds);
            if (!empty($batchIdsMap['customers'])) {
                $sourceCustomers = $this->customerRepository->getCustomers(
                    $batchIdsMap['customers'],
                    $includeOrders,
                    $context
                );
                /** @var CustomerEntity $customer */
                foreach ($sourceCustomers as $customer) {
                    if (filter_var($customer->getEmail(), FILTER_VALIDATE_EMAIL)) {
                        $customersFormatted[] = $this->buildCustomerEntity($customer, $includeOrders);
                    }
                }
            }

            if (!empty($batchIdsMap['subscribers'])) {
                $sourceSubscribers = $this->newsletterRecipientRepository->getNewsletterSubscribers(
                    $batchIdsMap['subscribers'],
                    $context
                );
                /** @var NewsletterRecipientEntity $subscriber */
                foreach ($sourceSubscribers as $subscriber) {
                    // Check if subscriber is already synchronized as customer
                    $customersFormatted[] = $this->buildSubscriberEntity($subscriber);
                }
            }
        } catch (\Exception $exception) {
            Logger::logError("Failed to get recipients with tags: {$exception->getMessage()}", 'Integration');
        }

        return $customersFormatted;
    }

    /**
     * /**
     * Gets all recipients IDs from source system.
     *
     * @return string[]
     *   Array of recipient IDs.
     *
     * @throws RecipientsGetException
     *   When recipients can't be fetched.
     */
    public function getAllRecipientsIds(): array
    {
        try {
            $customerIdsFormatted = $this->appendCustomerPrefix($this->customerRepository->getCustomerIds());
            $subscriberIdsFormatted = $this->appendSubscriberPrefix($this->newsletterRecipientRepository->getSubscriberIds());
            $idsFormatted = array_merge($customerIdsFormatted, $subscriberIdsFormatted);
        } catch (\Exception $exception) {
            Logger::logError("Failed to fetch recipients ids: {$exception->getMessage()}", 'Integration');
            throw new RecipientsGetException($exception->getMessage());
        }

        return array_values($idsFormatted);
    }

    /**
     * Informs service about completed synchronization of provided recipients IDs.
     *
     * @param array $recipientIds
     *   Array of recipient IDs that are successfully synchronized.
     */
    public function recipientSyncCompleted(array $recipientIds): void
    {
        // Intentionally left empty. We do not need this functionality
    }

    /**
     * Appends customer prefix to passed ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function appendCustomerPrefix(array $ids): array
    {
        return $this->appendPrefix($ids, self::CUSTOMER_PREFIX);
    }

    /**
     * Appends subscriber prefix to passed ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function appendSubscriberPrefix(array $ids): array
    {
        return $this->appendPrefix($ids, self::SUBSCRIBER_PREFIX);
    }

    /**
     * Appends given prefix on each id
     *
     * @param array $ids
     * @param string $prefix
     *
     * @return array
     */
    private function appendPrefix(array $ids, string $prefix): array
    {
        $formatted = [];
        foreach ($ids as $id) {
            $formatted[] = $prefix . $id;
        }

        return $formatted;
    }

    /**
     * @param CustomerEntity $customerEntity
     *
     * @param bool $includeOrders
     *
     * @return Recipient
     * @throws InconsistentCriteriaIdsException
     * @throws \Exception
     */
    private function buildCustomerEntity(CustomerEntity $customerEntity, bool $includeOrders): Recipient
    {
        $recipient = $this->createRecipientWithBaseInformation($customerEntity);
        $newsletterEntity = $this->newsletterRecipientRepository->getNewsletterSubscriberByEmail(
            $customerEntity->getEmail(),
            $this->getConfigService()->getShopwareContext()
        );
        $isSubscriber = $this->isCustomerSubscriber($newsletterEntity);
        $recipient->setInternalId(self::CUSTOMER_PREFIX . $customerEntity->getId());
        $recipient->setCustomerNumber($customerEntity->getCustomerNumber());
        $recipient->setNewsletterSubscription($isSubscriber);
        $recipient->setActive($isSubscriber);
        $this->setCustomerTags($customerEntity, $newsletterEntity, $recipient);
        $this->setSpecialTags($customerEntity, $recipient);

        $birthday = $customerEntity->getBirthday() ? new \DateTime("@{$customerEntity->getBirthday()->getTimestamp()}") : null;
        if ($birthday) {
            $recipient->setBirthday($birthday);
        }

        $address = $customerEntity->getDefaultShippingAddress() ?? $customerEntity->getDefaultBillingAddress();
        if ($address) {
            $this->setAddressData($recipient, $address);
        }

        $lastOrderDate = $customerEntity->getLastOrderDate();
        if ($lastOrderDate) {
            $recipient->setLastOrderDate(new \DateTime("@{$lastOrderDate->getTimestamp()}"));
        }

        if ($includeOrders && $customerEntity->getOrderCount() > 0) {
            $this->setOrderItems($customerEntity, $recipient);
        }

        return $recipient;
    }

    /**
     * @param NewsletterRecipientEntity $newsletterRecipientEntity
     *
     * @return Recipient
     * @throws \Exception
     */
    private function buildSubscriberEntity(NewsletterRecipientEntity $newsletterRecipientEntity): Recipient
    {
        $recipient = $this->createRecipientWithBaseInformation($newsletterRecipientEntity);
        $recipient->setInternalId(self::SUBSCRIBER_PREFIX . $newsletterRecipientEntity->getId());
        $newsletterStatus = $newsletterRecipientEntity->getStatus();
        $isActive = $newsletterStatus === NewsletterSubscribeRoute::STATUS_OPT_IN
            || $newsletterStatus === NewsletterSubscribeRoute::STATUS_DIRECT;
        $recipient->setActive($isActive);
        $recipient->setNewsletterSubscription($isActive);
        $specialTagCollection = new SpecialTagCollection();
        if ($isActive) {
            $specialTagCollection->addTag(SpecialTag::subscriber());
        }

        $recipient->setTags($this->getBaseTags($newsletterRecipientEntity));
        $recipient->setSpecialTags($specialTagCollection);
        $recipient->setStreet($newsletterRecipientEntity->getStreet());
        $recipient->setZip($newsletterRecipientEntity->getZipCode());
        $recipient->setCity($newsletterRecipientEntity->getCity());

        return $recipient;
    }

    /**
     * @param CustomerEntity|NewsletterRecipientEntity $entity
     *
     * @return Recipient
     * @throws \Exception
     */
    private function createRecipientWithBaseInformation(Entity $entity): Recipient
    {
        $recipient = new Recipient($entity->getEmail());
        $recipient->setFirstName($entity->getFirstName());
        $recipient->setLastName($entity->getLastName());
        $recipient->setTitle($entity->getTitle());
        $salutation = $entity->getSalutation() ? $entity->getSalutation()->getDisplayName() : '';
        $language = $entity->getLanguage() ? $entity->getLanguage()->getName() : '';
        $recipient->setSalutation($salutation);
        $recipient->setLanguage($language);
        $createdAt = $entity->getCreatedAt() ? new \DateTime("@{$entity->getCreatedAt()->getTimestamp()}") : null;
        if ($createdAt) {
            $recipient->setRegistered($createdAt);
        }

        $source = '';
        $salesChannel = $entity->getSalesChannel();
        if ($salesChannel) {
            $recipient->setShop($salesChannel->getName());
            if ($domains = $salesChannel->getDomains()) {
                $source = $domains->first() ? $domains->first()->getUrl() : '';
            }
        } else {
            Logger::logWarning("Sales channel not set on recipient with email: {$entity->getEmail()}", 'Integration');
        }

        $recipient->setSource($source);


        return $recipient;
    }

    /**
     * Removes prefix and maps ids by its type
     * @param array $batchIds
     *
     * @return array
     */
    private function createBatchIdsMap(array $batchIds): array
    {
        $batchIdsMap['customers'] = [];
        $batchIdsMap['subscribers'] = [];
        foreach ($batchIds as $batchId) {
            if (strpos($batchId, self::CUSTOMER_PREFIX) === 0) {
                $batchIdsMap['customers'][] = substr($batchId, strlen(self::CUSTOMER_PREFIX));
            } elseif (strpos($batchId, self::SUBSCRIBER_PREFIX) === 0) {
                $batchIdsMap['subscribers'][] = substr($batchId, strlen(self::SUBSCRIBER_PREFIX));
            }
        }

        return $batchIdsMap;
    }

    /**
     * @param EntityCollection $collection
     * @param string $type
     *
     * @return TagCollection
     */
    private function formatTags(EntityCollection $collection, string $type): TagCollection
    {
        $tagCollection = new TagCollection();
        /** @var CustomerGroupEntity|TagEntity $entity */
        foreach ($collection as $entity) {
            $tagCollection->addTag(new Tag($entity->getName(), $type));
        }

        return $tagCollection;
    }

    /**
     * @param Recipient $recipient
     * @param CustomerAddressEntity|null $address
     */
    private function setAddressData(Recipient $recipient, CustomerAddressEntity $address): void
    {
        $street = $address->getStreet();
        $street .= $address->getAdditionalAddressLine1() ? ' ' . $address->getAdditionalAddressLine1() : '';
        $street .= $address->getAdditionalAddressLine2() ? ' ' . $address->getAdditionalAddressLine2() : '';
        $recipient->setStreet($street);
        $recipient->setCity($address->getCity());
        $country = $address->getCountry() ? $address->getCountry()->getName() : '';
        $recipient->setCountry($country);
        $state = $address->getCountryState() ? $address->getCountryState()->getName() : '';
        $recipient->setState($state);
        $recipient->setPhone($address->getPhoneNumber());
        $recipient->setCompany($address->getCompany());
        $recipient->setZip($address->getZipcode());
    }

    /**
     * @param CustomerEntity $customerEntity
     * @param NewsletterRecipientEntity $newsletterRecipientEntity
     * @param Recipient $recipient
     */
    private function setCustomerTags(
        CustomerEntity $customerEntity,
        ?NewsletterRecipientEntity $newsletterRecipientEntity,
        Recipient $recipient
    ): void {
        $customerTags = $this->getBaseTags($customerEntity, $newsletterRecipientEntity);
        $groupName = $customerEntity->getGroup() ? $customerEntity->getGroup()->getTranslation('name') : null;
        if ($groupName) {
            $customerTags->addTag(new Tag($groupName, self::GROUP_TYPE));
        }

        if ($customerEntity->getGuest()) {
            $customerTags->addTag(new Tag('Guest', self::ACCOUNT_TYPE));
        } else {
            $customerTags->addTag(new Tag('Registered', self::ACCOUNT_TYPE));
        }

        $recipient->setTags($customerTags);
    }

    /**
     * @param CustomerEntity $customerEntity
     * @param Recipient $recipient
     */
    private function setSpecialTags(CustomerEntity $customerEntity, Recipient $recipient): void
    {
        $specialTags = new SpecialTagCollection([SpecialTag::customer()]);
        if ($recipient->getNewsletterSubscription()) {
            $specialTags->addTag(SpecialTag::subscriber());
        }

        if ($customerEntity->getOrderCount() > 0) {
            $specialTags->addTag(SpecialTag::buyer());
        }

        $recipient->setSpecialTags($specialTags);
    }

    /**
     * @param Entity $entity
     * @param Entity|null $additionalEntity
     *
     * @return TagCollection
     */
    private function getBaseTags(Entity $entity, ?Entity $additionalEntity = null): TagCollection
    {
        $customerTags = new TagCollection();
        $sourceTags = $entity->getTags();
        /** @var TagEntity $tagEntity */
        foreach ($sourceTags as $tagEntity) {
            $customerTags->addTag(new Tag($tagEntity->getName(), self::TAG_TYPE));
        }

        if ($additionalEntity && ($additionalTags = $additionalEntity->getTags())) {
            /** @var TagEntity $additionalTag */
            foreach ($additionalTags as $additionalTag) {
                $crTag = new Tag($additionalTag->getName(), self::TAG_TYPE);
                if (!$customerTags->hasTag($crTag)) {
                    $customerTags->addTag($crTag);
                }
            }
        }

        $salesChannel = $entity->getSalesChannel() ? $entity->getSalesChannel()->getName() : null;
        if ($salesChannel) {
            $customerTags->addTag(new Tag($salesChannel, self::STORE_TYPE));
        }

        return $customerTags;
    }

    /**
     * Set order items on recipient item
     *
     * @param CustomerEntity $customerEntity
     * @param Recipient $recipient
     *
     * @throws InconsistentCriteriaIdsException
     * @throws \Exception
     */
    private function setOrderItems(CustomerEntity $customerEntity, Recipient $recipient): void
    {
        $orderCustomers = $customerEntity->getOrderCustomers();
        if (!empty($orderCustomers)) {
            $orderItems = [];
            /** @var OrderCustomerEntity $orderCustomer */
            foreach ($orderCustomers as $orderCustomer) {
                $orderEntity = $orderCustomer->getOrder();
                if ($orderEntity) {
                    $orderItems[] = $this->orderService->formatItems($orderEntity->getLineItems());
                }
            }

            if (!empty($orderItems)) {
                $recipient->setOrders(array_merge(...$orderItems));
            }
        }
    }

    /**
     * Check if customer is subscriber
     *
     * @param NewsletterRecipientEntity|null $newsletterEntity
     *
     * @return bool
     */
    private function isCustomerSubscriber(?NewsletterRecipientEntity $newsletterEntity): bool
    {
        if ($newsletterEntity) {
            $status = $newsletterEntity->getStatus();
            return ($status === NewsletterSubscribeRoute::STATUS_OPT_IN) ||
                ($status === NewsletterSubscribeRoute::STATUS_DIRECT);
        }

        return false;
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
