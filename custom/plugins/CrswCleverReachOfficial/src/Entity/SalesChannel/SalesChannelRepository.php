<?php

namespace Crsw\CleverReachOfficial\Entity\SalesChannel;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * Class SalesChannelRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\SalesChannel
 */
class SalesChannelRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * SalesChannelRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns all sales channel
     *
     * @param Context $context
     *
     * @return SalesChannelCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getSalesChannels(Context $context): SalesChannelCollection
    {
        $criteria = new Criteria();
        /** @var SalesChannelCollection $collection */
        $collection = $this->baseRepository->search($criteria, $context)->getEntities();

        return  $collection;
    }

    /**
     * @param string $id
     *
     * @param Context $context
     *
     * @return SalesChannelEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getSalesChannelById(string $id, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->first();
    }

    /**
     * Returns SalesChannelEntity object by its shop name
     *
     * @param string $shopName
     * @param Context $context
     *
     * @return SalesChannelEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getSalesChannelByShopName(string $shopName, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $shopName));

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
