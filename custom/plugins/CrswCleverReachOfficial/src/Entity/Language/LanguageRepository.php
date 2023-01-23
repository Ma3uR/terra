<?php

namespace Crsw\CleverReachOfficial\Entity\Language;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageCollection;

/**
 * Class LanguageRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Language
 */
class LanguageRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * LanguageRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param Context $context
     *
     * @return LanguageCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getLanguages(Context $context): LanguageCollection
    {
        $criteria = new Criteria();
        /** @var LanguageCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return $collection;
    }
}