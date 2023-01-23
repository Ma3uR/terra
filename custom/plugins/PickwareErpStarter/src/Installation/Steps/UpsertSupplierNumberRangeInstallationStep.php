<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Installation\Steps;

use Pickware\PickwareErpStarter\Installation\Installer\NumberRangeInstaller;

class UpsertSupplierNumberRangeInstallationStep
{
    const SUPPLIER_NUMBER_RANGE_TECHNICAL_NAME = 'pickware_erp_supplier';
    const SUPPLIER_NUMBER_RANGE_PATTERN = '{n}';
    const SUPPLIER_NUMBER_RANGE_START = 1000;
    const SUPPLIER_NUMBER_RANGE_TRANSLATIONS = [
        'de-DE' => 'Lieferanten',
        'en-GB' => 'Suppliers',
    ];

    /**
     * @var NumberRangeInstaller
     */
    private $numberRangeInstaller;

    public function __construct(NumberRangeInstaller $numberRangeInstaller)
    {
        $this->numberRangeInstaller = $numberRangeInstaller;
    }

    public function install(): void
    {
        $this->numberRangeInstaller->ensureNumberRangeExists(
            self::SUPPLIER_NUMBER_RANGE_TECHNICAL_NAME,
            self::SUPPLIER_NUMBER_RANGE_PATTERN,
            self::SUPPLIER_NUMBER_RANGE_START,
            self::SUPPLIER_NUMBER_RANGE_TRANSLATIONS
        );
    }
}
