<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning\Model;

use DateTimeImmutable;
use Pickware\PickwareErpStarter\DemandPlanning\SessionConfiguration;
use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\User\UserEntity;

class DemandPlanningSessionEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var UserEntity|null
     */
    protected $user;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var DateTimeImmutable|null
     */
    protected $lastCalculation;

    /**
     * @var DemandPlanningListItemCollection|null
     */
    protected $demandPlanningListItems;

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        if ($this->user && $this->user->getId() !== $userId) {
            $this->user = null;
        }
        $this->userId = $userId;
    }

    public function getUser(): UserEntity
    {
        if (!$this->user) {
            throw new AssociationNotLoadedException('user', $this);
        }

        return $this->user;
    }

    public function setUser(UserEntity $user): void
    {
        $this->userId = $user->getId();
        $this->user = $user;
    }

    public function getConfiguration(): SessionConfiguration
    {
        return SessionConfiguration::fromArray($this->configuration);
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getLastCalculation(): ?DateTimeImmutable
    {
        return $this->lastCalculation;
    }

    public function setLastCalculation(?DateTimeImmutable $lastCalculation): void
    {
        $this->lastCalculation = $lastCalculation;
    }

    public function getDemandPlanningListItems(): DemandPlanningListItemCollection
    {
        if (!$this->demandPlanningListItems) {
            throw new AssociationNotLoadedException('demandPlanningListItems', $this);
        }

        return $this->demandPlanningListItems;
    }

    public function setDemandPlanningListItems(?DemandPlanningListItemCollection $demandPlanningListItems): void
    {
        $this->demandPlanningListItems = $demandPlanningListItems;
    }
}
