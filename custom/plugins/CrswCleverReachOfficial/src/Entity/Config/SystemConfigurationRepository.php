<?php

namespace Crsw\CleverReachOfficial\Entity\Config;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;

/**
 * Class SystemConfigurationRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Config
 */
class SystemConfigurationRepository
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
     * Returns default shop name
     *
     * @param Context $context
     *
     * @return string
     * @throws InconsistentCriteriaIdsException
     */
    public function getDefaultShopName(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('configurationKey', 'core.basicInformation.shopName'));

        /** @var SystemConfigEntity $configuration */
        $configuration = $this->baseRepository->search($criteria, $context)->first();
        if ($configuration) {
            return  (string)$configuration->getConfigurationValue();
        }

        return '';
    }
}
