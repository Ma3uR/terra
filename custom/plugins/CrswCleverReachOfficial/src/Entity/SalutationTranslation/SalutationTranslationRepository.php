<?php

namespace Crsw\CleverReachOfficial\Entity\SalutationTranslation;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Salutation\Aggregate\SalutationTranslation\SalutationTranslationEntity;

/**
 * Class SalutationTranslationRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Salutation
 */
class SalutationTranslationRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * SalutationTranslationRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $displayName
     * @param Context $context
     *
     * @return SalutationTranslationEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getSalutationByDisplayName(string $displayName, Context $context): ?SalutationTranslationEntity
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('displayName', $displayName));

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
