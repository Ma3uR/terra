<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;

class ContextFactory
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function deriveOrderContext(string $orderId, Context $context): Context
    {
        /** @var OrderEntity $order */
        $order = $this->entityManager->findByPrimaryKey(OrderDefinition::class, $orderId, $context, [
            'currency',
            'language.parent',
        ]);
        if (!$order) {
            throw new \InvalidArgumentException(sprintf('Order with ID=%s not found.', $orderId));
        }

        $languageIdChain = [
            $order->getLanguageId(),
            $order->getLanguage()->getParentId(),
            Defaults::LANGUAGE_SYSTEM,
        ];

        // See Shopware's code:
        // https://github.com/shopware/platform/blob/d36be415b939a03d5db294e294fc8004ee840889/src/Core/Checkout/Order/SalesChannel/OrderService.php#L500-L518
        return new Context(
            $context->getSource(),
            $context->getRuleIds(),
            $order->getCurrencyId(),
            $languageIdChain,
            $context->getVersionId(),
            $order->getCurrencyFactor(),
            // Unlike Shopware, I don't think it is a good idea to change the inheritance behavior when you change into
            // the context of an order.
            $context->considerInheritance(),
            $order->getTaxStatus()
        );
    }
}
