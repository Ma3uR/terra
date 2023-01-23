<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning\ModelExtension;

use Pickware\PickwareErpStarter\DemandPlanning\Model\DemandPlanningSessionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class DemandPlanningUserExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'pickwareErpDemandPlanningConfiguration',
                'id',
                'user_id',
                DemandPlanningSessionDefinition::class,
                false
            ))->addFlags(new CascadeDelete(false /* isCloneRelevant */))
        );
    }

    public function getDefinitionClass(): string
    {
        return UserDefinition::class;
    }
}
