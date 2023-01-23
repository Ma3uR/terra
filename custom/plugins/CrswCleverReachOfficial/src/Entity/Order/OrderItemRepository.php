<?php

namespace Crsw\CleverReachOfficial\Entity\Order;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class OrderItemRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Order
 */
class OrderItemRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * OrderItemRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns collection of order line items entities for passed itemIds
     *
     * @param array $itemIds
     * @param Context $context
     *
     * @return OrderLineItemCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderItems(array $itemIds, Context $context): OrderLineItemCollection
    {
        $criteria = new Criteria($itemIds);
        $criteria->addAssociation('order');
        $criteria->getAssociation('order')->addAssociations(['currency', 'customer']);
        /** @var OrderLineItemCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return  $collection;
    }

    /**
     * Returns order item by its order id
     *
     * @param string $orderId
     * @param Context $context
     *
     * @return OrderLineItemCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderItemsByOrderId(string $orderId, Context $context): OrderLineItemCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        /** @var OrderLineItemCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }
}
