<?php

namespace Tanmar\ProductReviews\Service;

use Tanmar\ProductReviews\Service\ConfigService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MinAggregation;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class ProductReviewsService {

    /**
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $productReviewRepository;

    /**
     *
     * @param ConfigService $configService
     * @param EntityRepositoryInterface $productRepository
     * @param EntityRepositoryInterface $orderRepository
     * @param EntityRepositoryInterface $productReviewRepository
     */
    public function __construct(ConfigService $configService, EntityRepositoryInterface $productRepository, EntityRepositoryInterface $orderRepository, EntityRepositoryInterface $productReviewRepository) {
        $this->configService = $configService;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     *
     * @param OrderEntity $order
     * @param Context $context
     * @return int
     */
    public function getMinVote(OrderEntity $order, Context $context): int {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addAggregation(
            new MinAggregation('min-points', 'points')
        );
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR, [
                new EqualsFilter('customerId', $order->getOrderCustomer()->getCustomerId()),
                new EqualsFilter('externalEmail', $order->getOrderCustomer()->getEmail())
                ]
            )
        );
        $criteria->addFilter(new EqualsFilter('product.orderLineItems.order.id', $order->getId()));
        $result = $this->productReviewRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('min-points');
        return (int) $aggregation->getMin();
    }

    /**
     *
     * @param OrderEntity $order
     * @param string $email
     * @param Context $context
     * @return bool
     */
    public function orderProductsHaveBeenRated(OrderEntity $order, Context $context): bool {
        $result = $this->getOrderRatings($order, $context);
        return count($result) >= count($order->getLineItems()->filterByType(LineItem::PRODUCT_LINE_ITEM_TYPE));
    }

    /**
     *
     * @param OrderEntity $order
     * @param string $email
     * @param Context $context
     * @return EntitySearchResult
     */
    public function getOrderRatings(OrderEntity $order, Context $context): ?EntitySearchResult {
        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR, [
                new EqualsFilter('customerId', $order->getOrderCustomer()->getCustomerId()),
                new EqualsFilter('externalEmail', $order->getOrderCustomer()->getEmail())
                ]
            )
        );
        $criteria->addFilter(new EqualsFilter('product.orderLineItems.order.id', $order->getId()));
        $result = $this->productReviewRepository->search($criteria, $context);
        return $result;
    }

}
