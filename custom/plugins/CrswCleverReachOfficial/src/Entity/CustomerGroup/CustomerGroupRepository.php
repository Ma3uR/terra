<?php

namespace Crsw\CleverReachOfficial\Entity\CustomerGroup;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class CustomerGroupRepository
 *
 * @package Crsw\CleverReachOfficial\CustomerGroup
 */
class CustomerGroupRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * CustomerGroupRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns customer groups
     *
     * @param Context $context
     *
     * @return CustomerGroupCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomerGroups(Context $context): CustomerGroupCollection
    {
        $criteria = new Criteria();
        /** @var CustomerGroupCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }

    /**
     * Returns customer group entity by its id
     *
     * @param string $id
     * @param Context $context
     *
     * @return CustomerGroupEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomerGroupById(string $id, Context $context): ?CustomerGroupEntity
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
