<?php

namespace Crsw\CleverReachOfficial\Entity\NewsletterRecipient;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidLengthException;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class SubscriberRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Subscriber
 */
class NewsletterRecipientRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * NewsletterRecipientRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param EntityRepositoryInterface $customerRepository
     * @param Connection $connection
     */
    public function __construct(
        EntityRepositoryInterface $baseRepository,
        EntityRepositoryInterface $customerRepository,
        Connection $connection
    ) {
        $this->baseRepository = $baseRepository;
        $this->customerRepository = $customerRepository;
        $this->connection = $connection;
    }

    /**
     * Returns newsletter ids which are not customers
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws InvalidUuidException
     * @throws InvalidUuidLengthException
     */
    public function getSubscriberIds(): array
    {
        $tableName = $this->baseRepository->getDefinition()->getEntityName();
        $customerTableName = $this->customerRepository->getDefinition()->getEntityName();
        $sql = "SELECT `id`, `email`
                FROM `{$tableName}`
                WHERE `email` NOT IN (
                    SELECT `email`
                    FROM `{$customerTableName}` 
                )";

        $results = $this->connection->executeQuery($sql)->fetchAll();
        $ids = [];
        foreach ($results as $item) {
            if (!empty($item['email']) && filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                $ids[] = Uuid::fromBytesToHex($item['id']);
            }
        }

        return  $ids;
    }

    /**
     * @param array $ids
     *
     * @param Context $context
     *
     * @return NewsletterRecipientCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getNewsletterSubscribers(array $ids, Context $context): NewsletterRecipientCollection
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociations(['tags', 'salesChannel.domains', 'salutation', 'language']);
        /** @var NewsletterRecipientCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }

    /**
     * Return newsletter recipient entity by email
     *
     * @param string $email
     * @param Context $context
     *
     * @return NewsletterRecipientEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getNewsletterSubscriberByEmail(string $email, Context $context): ?NewsletterRecipientEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['tags']);
        $criteria->addFilter(new EqualsFilter('email', $email));

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Return newsletter recipient entity by its id
     *
     * @param string $id
     * @param Context $context
     *
     * @return NewsletterRecipientEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getNewsletterSubscriberById(string $id, Context $context): ?NewsletterRecipientEntity
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Return newsletter recipient entity by its sales channel id which are not customers
     *
     * @param string $salesChannelId
     * @param array $customerEmails
     * @param Context $context
     *
     * @return NewsletterRecipientCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getNewsletterSubscriberBySalesChannelId(
        string $salesChannelId,
        array $customerEmails,
        Context $context
    ): NewsletterRecipientCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsAnyFilter('email', $customerEmails)]));
        /** @var NewsletterRecipientCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }

    /**
     * Updates newsletter status on newsletter subscriber entity
     *
     * @param string $id
     * @param string $status
     * @param Context $context
     */
    public function updateStatus(string $id, string $status, Context $context): void
    {
        $this->baseRepository->update([['id' => $id, 'status' => $status]], $context);
    }
}
