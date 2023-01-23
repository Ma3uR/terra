<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;

class PickwareOrderDeliveryCollection extends OrderDeliveryCollection
{
    public function getOrderDeliveryWithHighestShippingCosts(): ?OrderDeliveryEntity
    {
        $collectionCopy = self::createFrom($this);
        // Sort by shippingCosts ascending
        $collectionCopy->sort(function (OrderDeliveryEntity $a, OrderDeliveryEntity $b) {
            if ($a->getShippingCosts()->getTotalPrice() === $b->getShippingCosts()->getTotalPrice()) {
                return 0;
            }

            return $a->getShippingCosts()->getTotalPrice() < $b->getShippingCosts()->getTotalPrice() ? -1 : 1;
        });

        return $collectionCopy->last();
    }
}
