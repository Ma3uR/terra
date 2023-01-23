<?php

namespace Crsw\CleverReachOfficial\Entity\User;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Tag\TagEntity;
use Shopware\Core\System\User\UserEntity;

/**
 * Class UserRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\User
 */
class UserRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * UserRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param string $id
     *
     * @param Context $context
     *
     * @return TagEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getUserBy(string $id, Context $context): ?UserEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('locale');

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
