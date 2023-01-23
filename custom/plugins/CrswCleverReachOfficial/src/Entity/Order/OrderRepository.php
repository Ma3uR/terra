<?php

namespace Crsw\CleverReachOfficial\Entity\Order;

use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class OrderRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Order
 */
class OrderRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * OrderRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns collection of order entities for passed orderIds
     *
     * @param array $orderIds
     * @param Context $context
     *
     * @return OrderCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrders(array $orderIds, Context $context): OrderCollection
    {
        $criteria = new Criteria($orderIds);
        /** @var OrderCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return  $collection;
    }
}
