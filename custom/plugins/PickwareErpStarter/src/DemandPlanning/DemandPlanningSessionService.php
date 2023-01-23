<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning;

use DateInterval;
use DateTime;
use Doctrine\DBAL\Connection;
use Pickware\PickwareErpStarter\DemandPlanning\Model\DemandPlanningSessionDefinition;
use Pickware\PickwareErpStarter\DemandPlanning\Model\DemandPlanningSessionEntity;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class DemandPlanningSessionService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DemandPlanningCalculationService
     */
    private $calculationService;
    /**
     * @var Connection
     */
    private $db;

    public function __construct(
        Connection $db,
        EntityManager $entityManager,
        DemandPlanningCalculationService $calculationService
    ) {
        $this->entityManager = $entityManager;
        $this->calculationService = $calculationService;
        $this->db = $db;
    }

    public function ensureSessionExists($userId, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('userId', $userId));
        /** @var DemandPlanningSessionEntity|null $existingConfiguration */
        $session = $this->entityManager->findOneBy(
            DemandPlanningSessionDefinition::class,
            $criteria,
            $context
        );

        if (!$session) {
            $payload = [
                'userId' => $userId,
                'configuration' => SessionConfiguration::createDefault()->jsonSerialize(),
                'lastCalculation' => null,
            ];
            $this->entityManager->create(DemandPlanningSessionDefinition::class, [$payload], $context);

            $session = $this->entityManager->findOneBy(
                DemandPlanningSessionDefinition::class,
                $criteria,
                $context
            );
        }

        if (!$session->getLastCalculation()
            || ($session->getLastCalculation() < $this->getLastCalculationThreshold())
        ) {
            $this->calculationService->calculateDemand($session->getId(), $context);
        }

        return $session->getId();
    }

    /**
     * Clean up function that removes all demand (demand planning list items) from the database whose calculation
     * is outdated. More precise: whose demand session last-calculation property is outdated.
     *
     * We keep the session entities so that the session configuration is not lost. Note that the same
     * 'lastCalculationThreshold' is used as in ensureSessionExists so that outdated sessions will be recalculated.
     */
    public function clearOutdatedDemand(): void
    {
        $lastCalculationThreshold = $this->getLastCalculationThreshold();
        $this->db->executeStatement(
            'DELETE `pickware_erp_demand_planning_list_item`
            FROM `pickware_erp_demand_planning_list_item`
            LEFT JOIN `pickware_erp_demand_planning_session`
            ON `pickware_erp_demand_planning_list_item`.`demand_planning_session_id` = `pickware_erp_demand_planning_session`.`id`
            WHERE `pickware_erp_demand_planning_session`.`last_calculation` <= :lastCalculationThreshold',
            [
                'lastCalculationThreshold' => $lastCalculationThreshold->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }

    private function getLastCalculationThreshold(): DateTime
    {
        $threshold = new DateTime();
        $threshold->sub(new DateInterval('P1D'));

        return $threshold;
    }
}
