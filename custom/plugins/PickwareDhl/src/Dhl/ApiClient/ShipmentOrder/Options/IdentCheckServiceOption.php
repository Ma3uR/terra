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

use DateTime;

class IdentCheckServiceOption extends ServiceOption
{
    public const SUPPORTED_AGES = [
        16,
        18,
    ];

    /**
     * @param string $givenName First name
     * @param string $surname Last name
     * @param DateTime $dateOfBirth
     * @param int $minimumAge
     */
    public function __construct(string $givenName, string $surname, DateTime $dateOfBirth, int $minimumAge)
    {
        if (!in_array($minimumAge, self::SUPPORTED_AGES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Age %d is not supported by service "IdentCheck"',
                $minimumAge
            ));
        }

        parent::__construct('IdentCheck', [
            'Ident' => [
                'givenName' => $givenName,
                'surname' => $surname,
                'dateOfBirth' => $dateOfBirth->format('Y-m-d'),
                'minimumAge' => 'A' . strval($minimumAge),
            ],
        ]);
    }
}
