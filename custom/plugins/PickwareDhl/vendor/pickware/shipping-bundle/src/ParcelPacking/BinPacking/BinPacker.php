<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelPacking\BinPacking;

use Pickware\ShippingBundle\Parcel\ParcelItem;

interface BinPacker
{
    /**
     * @param ParcelItem[] $itemsToDistribute
     * @param array $configuration
     * @return ParcelItem[][]
     */
    public function packIntoBins(array $itemsToDistribute, array $configuration): array;
}
