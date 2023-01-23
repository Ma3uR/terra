<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\CriteriaQueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityManager
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var CriteriaQueryBuilder
     */
    private $criteriaQueryBuilder;

    public function __construct(
        ContainerInterface $container,
        Connection $db,
        CriteriaQueryBuilder $criteriaQueryBuilder
    ) {
        $this->container = $container;
        $this->db = $db;
        $this->criteriaQueryBuilder = $criteriaQueryBuilder;
    }

    /**
     * @param string $entityDefinitionClassName
     * @param mixed $primaryKey
     * @param Context $context
     * @param array $associations
     * @return Entity|null
     */
    public function findByPrimaryKey(
        string $entityDefinitionClassName,
        $primaryKey,
        Context $context,
        array $associations = []
    ): ?Entity {
        $repository = $this->getRepository($entityDefinitionClassName);
        $criteria = new Criteria([$primaryKey]);
        if (count($associations) !== 0) {
            $criteria->addAssociations($associations);
        }

        $result = $repository->search($criteria, $context);

        if ($result->count() > 1) {
            throw DataAbstractionLayerException::moreThanOneEntityInResultSet(__METHOD__);
        }

        return $result->first();
    }

    /**
     * @param string $entityDefinitionClassName
     * @param array|Criteria $criteria
     * @param Context $context
     * @param array $associations
     * @return EntityCollection
     */
    public function findBy(
        string $entityDefinitionClassName,
        $criteria,
        Context $context,
        array $associations = []
    ): EntityCollection {
        if (is_array($criteria)) {
            $criteria = self::createCriteriaFromArray($criteria);
        } elseif (!($criteria instanceof Criteria)) {
            throw new InvalidArgumentException(sprintf(
                'Parameter $criteria must be instance of %s or array.',
                Criteria::class
            ));
        }

        if (count($associations) !== 0) {
            $criteria->addAssociations($associations);
        }

        $repository = $this->getRepository($entityDefinitionClassName);
        $searchResult = $repository->search($criteria, $context);

        $collectionClassName = $this->getEntityDefinition($entityDefinitionClassName)->getCollectionClass();

        return new $collectionClassName($searchResult->getElements());
    }

    /**
     * @param string $entityDefinitionClassName
     * @param array|Criteria $criteria
     * @param Context $context
     * @param array $associations
     * @return Entity|null
     */
    public function findOneBy(
        string $entityDefinitionClassName,
        $criteria,
        Context $context,
        array $associations = []
    ): ?Entity {
        $result = $this->findBy($entityDefinitionClassName, $criteria, $context, $associations);

        if ($result->count() > 1) {
            throw DataAbstractionLayerException::moreThanOneEntityInResultSet(__METHOD__);
        }

        return $result->first();
    }

    /**
     * @param string $entityDefinitionClassName
     * @param Context $context
     * @param array $associations
     * @return EntityCollection
     */
    public function findAll(
        string $entityDefinitionClassName,
        Context $context,
        array $associations = []
    ): EntityCollection {
        return $this->findBy($entityDefinitionClassName, [], $context, $associations);
    }

    /**
     * @param string $entityDefinitionClassName
     * @param array $payload
     * @param Context $context
     * @return EntityWrittenContainerEvent
     */
    public function create(
        string $entityDefinitionClassName,
        array $payload,
        Context $context
    ): EntityWrittenContainerEvent {
        if (count($payload) === 0) {
            return EntityWrittenContainerEvent::createWithWrittenEvents([], $context, []);
        }

        return $this->getRepository($entityDefinitionClassName)->create($payload, $context);
    }

    /**
     * @param string $entityDefinitionClassName
     * @param array $payload
     * @param Context $context
     * @return EntityWrittenContainerEvent
     */
    public function upsert(
        string $entityDefinitionClassName,
        array $payload,
        Context $context
    ): EntityWrittenContainerEvent {
        if (count($payload) === 0) {
            return EntityWrittenContainerEvent::createWithWrittenEvents([], $context, []);
        }

        return $this->getRepository($entityDefinitionClassName)->upsert($payload, $context);
    }

    /**
     * @param string $entityDefinitionClassName
     * @param array $payload
     * @param Context $context
     * @return EntityWrittenContainerEvent
     */
    public function update(
        string $entityDefinitionClassName,
        array $payload,
        Context $context
    ): EntityWrittenContainerEvent {
        if (count($payload) === 0) {
            return EntityWrittenContainerEvent::createWithWrittenEvents([], $context, []);
        }

        return $this->getRepository($entityDefinitionClassName)->update($payload, $context);
    }

    /**
     * @param string $entityDefinitionClassName
     * @param array $ids
     * @param Context $context
     * @return EntityWrittenContainerEvent
     */
    public function delete(string $entityDefinitionClassName, array $ids, Context $context): EntityWrittenContainerEvent
    {
        if (count($ids) === 0) {
            return EntityWrittenContainerEvent::createWithDeletedEvents([], $context, []);
        }

        $ids = array_values($ids);

        // Convert the $ids to an array of associative arrays if not passed as such
        if (!is_array($ids[0])) {
            $entityDefinition = $this->getEntityDefinition($entityDefinitionClassName);
            $primaryKeyFields = $entityDefinition->getPrimaryKeys()->filter(function (Field $field) {
                return !($field instanceof VersionField);
            });
            $primaryKey = $primaryKeyFields->first();
            $ids = array_map(function ($id) use ($primaryKey) {
                return [
                    $primaryKey->getPropertyName() => $id,
                ];
            }, $ids);
        }

        return $this->getRepository($entityDefinitionClassName)->delete($ids, $context);
    }

    /**
     * @param string $entityDefinitionClassName
     * @return EntityRepositoryInterface
     */
    public function getRepository(string $entityDefinitionClassName): EntityRepositoryInterface
    {
        $entityName = $this->getEntityDefinition($entityDefinitionClassName)->getEntityName();

        return $this->container->get(sprintf('%s.repository', $entityName));
    }

    /**
     * @param string $entityDefinitionClassName
     * @return EntityDefinition
     */
    public function getEntityDefinition(string $entityDefinitionClassName): EntityDefinition
    {
        /** @var EntityDefinition $entityDefinition */
        $entityDefinition = $this->container->get($entityDefinitionClassName);

        return $entityDefinition;
    }

    /**
     * @param string $entityDefinitionClassName
     * @param Criteria|array $criteria
     * @param Context $context
     * @throws DataAbstractionLayerException
     */
    public function lockPessimistically(string $entityDefinitionClassName, $criteria, Context $context): void
    {
        if (!$this->db->isTransactionActive()) {
            // Pessimistic locking can happen in transactions exclusively
            throw DataAbstractionLayerException::transactionNecessaryForPessimisticLocking();
        }

        // Convert criteria array to Criteria object
        if (is_array($criteria)) {
            $criteria = self::createCriteriaFromArray($criteria);
        } elseif (!($criteria instanceof Criteria)) {
            throw new InvalidArgumentException(sprintf(
                'Parameter $criteria must be instance of %s or array.',
                Criteria::class
            ));
        }

        // Create queryBuilder for Criteria object
        $entityDefinition = $this->getEntityDefinition($entityDefinitionClassName);
        $queryBuilder = $this->criteriaQueryBuilder->build(
            new QueryBuilder($this->db),
            $entityDefinition,
            $criteria,
            $context
        );
        $queryBuilder->addSelect($this->db->quoteIdentifier(sprintf('%s.id', $entityDefinition->getEntityName())));

        // Execute locking SQL
        $sql = $queryBuilder->getSQL() . ' ' . $this->db->getDatabasePlatform()->getWriteLockSQL();
        $this->db->executeStatement($sql, $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
    }

    public function transactional(Context $context, callable $callback)
    {
        return $this->db->transactional(function () use ($callback, $context) {
            return $callback($this, $context);
        });
    }

    public static function createCriteriaFromArray(array $array): Criteria
    {
        $criteria = new Criteria();
        foreach ($array as $field => $criterion) {
            if (is_array($criterion)) {
                $criteria->addFilter(new EqualsAnyFilter($field, $criterion));
            } else {
                $criteria->addFilter(new EqualsFilter($field, $criterion));
            }
        }

        return $criteria;
    }
}
