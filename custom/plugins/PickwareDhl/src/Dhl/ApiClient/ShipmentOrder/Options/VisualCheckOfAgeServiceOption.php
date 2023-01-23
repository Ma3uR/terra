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

/**
 * "Alterssichtprüfung"
 */
class VisualCheckOfAgeServiceOption extends ServiceOption
{
    public const SUPPORTED_AGES = [
        16,
        18,
    ];

    /**
     * @inheritDoc
     */
    public function __construct(int $age)
    {
        if (!in_array($age, self::SUPPORTED_AGES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Age %d is not supported by service "VisualCheckOfAge"',
                $age
            ));
        }
        parent::__construct('VisualCheckOfAge', [
            'type' => 'A' . $age,
        ]);
    }
}
