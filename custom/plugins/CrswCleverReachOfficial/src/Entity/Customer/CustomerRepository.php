<?php

namespace Crsw\CleverReachOfficial\Entity\Customer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class CustomerRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Customer
 */
class CustomerRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * CustomerRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     */
    public function __construct(EntityRepositoryInterface $baseRepository, Connection $connection)
    {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
    }

    /**
     * Returns customer ids
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCustomerIds(): array
    {
        $tableName = $this->baseRepository->getDefinition()->getEntityName();
        $sql = "SELECT `id`, `email` FROM `{$tableName}`";

        $results = $this->connection->executeQuery($sql)->fetchAll();
        $ids = [];
        foreach ($results as $item) {
            if (!empty($item['email']) && filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                $ids[] = Uuid::fromBytesToHex($item['id']);
            }
        }

        return $ids;
    }

    /**
     * @param array $ids
     * @param bool $includeOrders
     * @param Context $context
     *
     * @return CustomerCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomers(array $ids, bool $includeOrders, Context $context): CustomerCollection
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociations([
            'group',
            'salutation',
            'defaultBillingAddress.country',
            'defaultBillingAddress.countryState',
            'defaultShippingAddress.country',
            'defaultShippingAddress.countryState',
            'language',
            'tags',
            'salesChannel.domains',
            'birthday',
        ]);
        if ($includeOrders) {
            $criteria->addAssociations([
                'orderCustomers.order.lineItems.order.customer',
                'orderCustomers.order.lineItems.order.currency',
            ]);
        }

        /** @var CustomerCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }

    /**
     * Returns IDs of all guest customers in the system.
     *
     * @param Context $context
     *
     * @return mixed|null
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function getGuestCustomerIds(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('guest', 1));

        $customers = $this->baseRepository->search($criteria, $context)->getEntities();
        $ids = [];
        /** @var CustomerEntity $customer */
        foreach ($customers as $customer) {
            if (filter_var($customer->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $ids[] = $customer->getId();
            }
        }

        return $ids;
    }

    /**
     * Return customer entity by email
     *
     * @param string $email
     * @param Context $context
     *
     * @return CustomerEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomerByEmail(string $email, Context $context): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Return customer entity by its unique id
     *
     * @param string $id
     * @param Context $context
     *
     * @return CustomerEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomerById(string $id, Context $context): ?CustomerEntity
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Updates newsletter status
     *
     * @param string $id
     * @param bool $status
     * @param Context|null $context
     */
    public function updateStatus(string $id, bool $status, Context $context): void
    {
        $this->baseRepository->update([['id' => $id, 'newsletter' => $status]], $context);
    }

    /**
     * Returns customers by its customer group
     *
     * @param string $customerGroupId
     * @param Context $context
     *
     * @return CustomerCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomersByCustomerGroup(string $customerGroupId, Context $context): CustomerCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('groupId', $customerGroupId));
        /** @var CustomerCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }

    /**
     * Returns customers by its sales channel
     *
     * @param string $salesChannelId
     * @param Context $context
     *
     * @return CustomerCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomersBySalesChannelId(string $salesChannelId, Context $context): CustomerCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        /** @var CustomerCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }
}
