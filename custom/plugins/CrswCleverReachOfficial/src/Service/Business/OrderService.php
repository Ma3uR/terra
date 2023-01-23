<?php

namespace Crsw\CleverReachOfficial\Service\Business;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\OrderItem;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\OrderItems;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Order\OrderItemRepository;
use Crsw\CleverReachOfficial\Entity\Order\OrderRepository;
use Crsw\CleverReachOfficial\Entity\Product\ProductRepository;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;

/**
 * Class OrderService
 *
 * @package Crsw\CleverReachOfficial\Service\Business
 */
class OrderService implements OrderItems
{
    public const DEFAULT_CURRENCY = 'EUR';

    /**
     * @var OrderItemRepository
     */
    private $orderItemsRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * OrderService constructor.
     *
     * @param OrderItemRepository $orderItemsRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        OrderItemRepository $orderItemsRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ) {
        $this->orderItemsRepository = $orderItemsRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Gets order items by passed IDs.
     *
     * @param string[]|null $orderItemsIds Array of order item IDs that needs to be fetched.
     *
     * @return OrderItem[]
     *   Array of OrderItems that matches passed IDs.
     */
    public function getOrderItems($orderItemsIds): array
    {
        try {
            $sourceOrderItems = $this->orderItemsRepository->getOrderItems(
                array_values($orderItemsIds),
                $this->getConfigService()->getShopwareContext()
            );

            return $this->formatItems($sourceOrderItems);
        } catch (\Exception $exception) {
            Logger::logError("Failed to fetch order items from database: {$exception->getMessage()}", 'Integration');
        }

        return [];
    }

    /**
     * Format order items as array of order items entities
     *
     * @param OrderLineItemCollection $sourceOrderItems
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     * @throws \Exception
     */
    public function formatItems(OrderLineItemCollection $sourceOrderItems): array
    {
        $formattedOrderItems = [];
        $productMap = $this->fetchProducts($sourceOrderItems);
        /** @var OrderLineItemEntity $sourceOrderItem */
        foreach ($sourceOrderItems as $sourceOrderItem) {
            $formattedOrderItems[] = $this->buildOrderItemEntity($sourceOrderItem, $productMap[$sourceOrderItem->getIdentifier()]);
        }

        return $formattedOrderItems;
    }

    /**
     * Builds order item from source
     *
     * @param OrderLineItemEntity $sourceOrderItem
     * @param ProductEntity|null $productEntity
     *
     * @return OrderItem|null
     * @throws \Exception
     */
    private function buildOrderItemEntity(OrderLineItemEntity $sourceOrderItem, ?ProductEntity $productEntity): ?OrderItem
    {
        $sourceOrder = $sourceOrderItem->getOrder();
        if (!$sourceOrder) {
            return null;
        }

        $currency = $sourceOrder->getCurrency() ? $sourceOrder->getCurrency()->getIsoCode() : self::DEFAULT_CURRENCY;
        $item = new OrderItem($sourceOrderItem->getUniqueIdentifier(), $sourceOrderItem->getLabel(), $sourceOrder->getOrderNumber());
        $item->setAmount($sourceOrderItem->getQuantity());
        $item->setCurrency($currency);
        $item->setPrice($sourceOrderItem->getTotalPrice());
        $createdAt = $sourceOrder->getCreatedAt() ?? new \DateTime();
        $item->setStamp(new \DateTime('@' . $createdAt->getTimestamp()));
        $source = $sourceOrder->getSalesChannel() ? $sourceOrder->getSalesChannel()->getTranslation('name') : '';
        $item->setProductSource($source);
        $email = $sourceOrder->getOrderCustomer() ? $sourceOrder->getOrderCustomer()->getEmail() : '';
        $item->setRecipientEmail($email);
        if ($productEntity) {
            $item->setProductId($productEntity->getProductNumber());
            $brand = $productEntity->getManufacturer() ? $productEntity->getManufacturer()->getTranslation('name') : '';
            $item->setBrand($brand);
            $item->setProductCategory($this->getProductCategories($productEntity));
            $item->setAttributes($this->getProductProperties($productEntity));
        }

        return $item;
    }

    /**
     * Returns map of products [product_id => ProductEntity]
     *
     * @param OrderLineItemCollection $sourceOrderItems
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function fetchProducts(OrderLineItemCollection $sourceOrderItems): array
    {
        $productIds = [];
        /** @var OrderLineItemEntity $sourceOrderItem */
        foreach ($sourceOrderItems as $sourceOrderItem) {
            $productId = $sourceOrderItem->getIdentifier();
            if (!in_array($productId, $productIds, true)) {
                $productIds[] = $productId;
            }
        }

        $productsMap = [];
        $products = $this->productRepository->getProducts($productIds, $this->getConfigService()->getShopwareContext());
        /** @var ProductEntity $productEntity */
        foreach ($products as $productEntity) {
            $productsMap[$productEntity->getId()] = $productEntity;
        }

        return $productsMap;
    }

    /**
     * Returns product categories
     *
     * @param ProductEntity $productEntity
     *
     * @return array
     */
    private function getProductCategories(ProductEntity $productEntity): array
    {
        $categoriesFormatted = [];
        /** @var CategoryEntity $category */
        foreach ($productEntity->getCategories() as $category) {
            $categoriesFormatted[] = $category->getTranslation('name');
        }

        return $categoriesFormatted;
    }

    /**
     * Returns product properties
     *
     * @param ProductEntity $productEntity
     *
     * @return array
     */
    private function getProductProperties(ProductEntity $productEntity): array
    {
        $properties = [];
        /** @var PropertyGroupOptionEntity $property */
        foreach ($productEntity->getProperties() as $property) {
            $propertyGroup = $property->getGroup() ? $property->getGroup()->getTranslation('name') : 'custom';
            $properties[$propertyGroup] = $property->getTranslation('name');

        }

        return $properties;
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
