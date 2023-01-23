<?php

namespace Crsw\CleverReachOfficial\Entity\Product;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ProductRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Product
 */
class ProductRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * ProductRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param array $productIds
     * @param Context $context
     *
     * @return ProductCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getProducts(array $productIds, Context $context): ProductCollection
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociations(['manufacturer', 'categories', 'properties.group']);

        /** @var ProductCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return  $collection;
    }

    /**
     * @param string $productId
     * @param Context $context
     *
     * @return ProductEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getProductById(string $productId, Context $context): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociations(['translations', 'media', 'properties', 'categories', 'options']);

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * @param string $productNumber
     * @param Context $context
     *
     * @return ProductCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getProductsByNumber(string $productNumber, Context $context): ProductCollection
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('productNumber', $productNumber));
        $criteria->addAssociations(['translations', 'media', 'parent', 'properties', 'categories', 'options', 'prices']);

        /** @var ProductCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return  $collection;
    }
}
