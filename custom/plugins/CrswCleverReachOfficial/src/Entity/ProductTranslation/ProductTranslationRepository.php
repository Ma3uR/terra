<?php


namespace Crsw\CleverReachOfficial\Entity\ProductTranslation;

use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ProductTranslationRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\ProductTranslation
 */
class ProductTranslationRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * ProductTranslationRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $name
     * @param string $languageId
     * @param Context $context
     *
     * @return ProductTranslationCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getProductsTranslationsByName(
        string $name,
        string $languageId,
        Context $context
    ): ProductTranslationCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('name', $name))
            ->addFilter(new EqualsFilter('languageId', $languageId));

        /** @var ProductTranslationCollection $results */
        $results = $this->baseRepository->search($criteria, $context)->getEntities();

        return $results;
    }
}
