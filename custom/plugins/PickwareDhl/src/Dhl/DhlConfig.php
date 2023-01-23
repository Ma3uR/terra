<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl;

use Pickware\PickwareDhl\Dhl\ApiClient\DhlApiClientConfig;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlBankTransferData;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlBillingInformation;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlProduct;
use Pickware\ShippingBundle\Config\ConfigDecoratorTrait;

class DhlConfig
{
    use ConfigDecoratorTrait;

    public const CONFIG_DOMAIN = 'PickwareDhl.dhl';

    public function getDhlApiClientConfig(): DhlApiClientConfig
    {
        $this->config->assertNotEmpty('username');
        $this->config->assertNotEmpty('password');

        return new DhlApiClientConfig(
            $this->config['username'],
            $this->config['password'],
            $this->config['useTestingEndpoint'] ?? false
        );
    }

    public function getBankTransferData(): ?DhlBankTransferData
    {
        $bankTransferDataConfig = [];
        foreach ($this->config as $key => $value) {
            if (mb_strpos($key, 'bankTransferData') === 0) {
                $shortKey = lcfirst(str_replace('bankTransferData', '', $key));
                $bankTransferDataConfig[$shortKey] = $value;
            }
        }

        $this->config->assertNotEmpty('bankTransferDataIban');
        $this->config->assertNotEmpty('bankTransferDataBankName');
        $this->config->assertNotEmpty('bankTransferDataAccountOwnerName');

        return new DhlBankTransferData($bankTransferDataConfig);
    }

    public function getBillingInformation(): DhlBillingInformation
    {
        $this->assertValidCustomerNumber();
        $billingInformation = new DhlBillingInformation($this->config['customerNumber']);

        foreach (DhlProduct::getList() as $dhlProduct) {
            $participationConfigKey = 'participation' . $dhlProduct->getCode();
            if (isset($this->config[$participationConfigKey]) && $this->config[$participationConfigKey] !== '') {
                $billingInformation->setParticipationForProduct($dhlProduct, $this->config[$participationConfigKey]);
            }
            $returnParticipationConfigKey = 'returnParticipation' . $dhlProduct->getCode();
            if (isset($this->config[$returnParticipationConfigKey])
                && $this->config[$returnParticipationConfigKey] !== ''
            ) {
                $billingInformation->setReturnParticipationForProduct(
                    $dhlProduct,
                    $this->config[$returnParticipationConfigKey]
                );
            }
        }

        return $billingInformation;
    }

    public function isEmailTransferAllowed(): bool
    {
        return $this->config['gdprAllowEmail'] ?? false;
    }

    public function isPhoneTransferAllowed(): bool
    {
        return $this->config['gdprAllowPhone'] ?? false;
    }

    public function isDispatchNotificationEnabled(): bool
    {
        return $this->config['enableDispatchNotification'] ?? false;
    }

    private function assertValidCustomerNumber(): void
    {
        $this->config->assertNotEmpty('customerNumber');
        $this->config->assertMatchRegex('customerNumber', '/^\\d{10}$/');
    }
}
