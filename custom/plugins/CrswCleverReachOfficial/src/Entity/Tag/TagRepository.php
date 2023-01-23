<?php

namespace Crsw\CleverReachOfficial\Entity\Tag;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Tag\TagEntity;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class TagRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Tag
 */
class TagRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * TagRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository, Connection $connection)
    {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
    }

    /**
     * Returns all customer tags
     *
     * @param Context $context
     *
     * @throws InconsistentCriteriaIdsException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTags(Context $context): EntityCollection
    {
        $ids = $this->getCustomerTagIds();
        if (empty($ids)) {
            return new EntityCollection();
        }
        $criteria = new Criteria($ids);

        return $this->baseRepository->search($criteria, $context)->getEntities();
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getCustomerTagIds(): array
    {
        $sql = 'SELECT DISTINCT `tag_id` FROM `customer_tag`';
        $results = $this->connection->executeQuery($sql)->fetchAll();
        $ids = [];
        foreach ($results as $item) {
            $ids[] = Uuid::fromBytesToHex($item['tag_id']);
        }

        return $ids;
    }

    /**
     * @param string $id
     *
     * @param Context $context
     *
     * @return TagEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getTagById(string $id, Context $context): ?TagEntity
    {
        $criteria = new Criteria([$id]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}
