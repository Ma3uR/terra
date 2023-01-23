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

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(DemandPlanningSessionEntity $entity)
 * @method void set(string $key, DemandPlanningSessionEntity $entity)
 * @method DemandPlanningSessionEntity[] getIterator()
 * @method DemandPlanningSessionEntity[] getElements()
 * @method DemandPlanningSessionEntity|null get(string $key)
 * @method DemandPlanningSessionEntity|null first()
 * @method DemandPlanningSessionEntity|null last()
 */
class DemandPlanningSessionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DemandPlanningSessionEntity::class;
    }
}
