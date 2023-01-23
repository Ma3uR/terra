<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options;

class PreferredDayServiceOption extends ServiceOption
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * @inheritDoc
     */
    public function __construct(\DateTimeInterface $preferredDay)
    {
        parent::__construct('PreferredDay', [
            'details' => $preferredDay->format(self::DATE_FORMAT),
        ]);
    }
}
