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
 * Service endorsement is used to specify handling if recipient not met. There are two options: IMMEDIATE (Sending back
 * to sender), ABANDONMENT (Abandonment of parcel at the hands of sender (free of charge))
 */
class EndorsementServiceOption extends ServiceOption
{
    public const IMMEDIATE = 'IMMEDIATE';
    public const ABANDONMENT = 'ABANDONMENT';

    public const TYPES = [
        self::IMMEDIATE,
        self::ABANDONMENT,
    ];

    public function __construct(string $type)
    {
        if (!in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException(sprintf('Type %s is not supported by service "Endorsement"', $type));
        }

        parent::__construct('Endorsement', [
            'type' => $type,
        ]);
    }
}
