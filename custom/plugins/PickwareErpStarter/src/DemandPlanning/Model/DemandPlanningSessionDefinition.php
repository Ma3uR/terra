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

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class DemandPlanningSessionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'pickware_erp_demand_planning_session';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return DemandPlanningSessionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DemandPlanningSessionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('user_id', 'userId', UserDefinition::class, 'id'))->addFlags(new Required()),
            new OneToOneAssociationField('user', 'user_id', 'id', UserDefinition::class, false),

            new JsonField('configuration', 'configuration'),
            new DateTimeField('last_calculation', 'lastCalculation'),

            // Reverse side associations
            (new OneToManyAssociationField(
                'demandPlanningListItems',
                DemandPlanningListItemDefinition::class,
                'demand_planning_configuration_id',
                'id'
            ))->addFlags(new CascadeDelete()),
        ]);
    }
}
