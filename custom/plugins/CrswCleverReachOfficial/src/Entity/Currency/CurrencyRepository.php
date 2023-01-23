<?php

namespace Crsw\CleverReachOfficial\Entity\Currency;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * Class CurrencyRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Currency
 */
class CurrencyRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * CurrencyRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $currencyId
     * @param Context $context
     *
     * @return CurrencyEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getCurrencyById(string $currencyId, Context $context): ?CurrencyEntity
    {
        $criteria = new Criteria([$currencyId]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
