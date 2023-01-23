<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient;

use Pickware\ShippingBundle\Soap\RequestHandler\AntiCompressionSoapRequestHandler;
use Pickware\ShippingBundle\Soap\RequestHandler\SoapRequestLoggingHandler;
use Pickware\ShippingBundle\Soap\SoapApiClient;
use Psr\Log\LoggerInterface;

class DhlApiClientFactory
{
    public const API_VERSION_MAJOR = 3;
    public const API_VERSION_MINOR = 1;
    public const API_VERSION_PATCH = 2;
    public const API_VERSION_STRING = self::API_VERSION_MAJOR . '.' . self::API_VERSION_MINOR . '.' . self::API_VERSION_PATCH;

    /**
     * @var DhlSoapClientFactory
     */
    private $dhlSoapClientFactory;

    /**
     * @var LoggerInterface
     */
    private $dhlRequestLogger;

    public function __construct(DhlSoapClientFactory $dhlSoapClientFactory, LoggerInterface $dhlRequestLogger)
    {
        $this->dhlSoapClientFactory = $dhlSoapClientFactory;
        $this->dhlRequestLogger = $dhlRequestLogger;
    }

    public function createDhlApiClient(DhlApiClientConfig $dhlApiClientConfig): SoapApiClient
    {
        $soapClient = $this->dhlSoapClientFactory->createDhlSoapClient($dhlApiClientConfig, self::API_VERSION_STRING);

        $dhlApiClient = new SoapApiClient($soapClient);
        $dhlApiClient->use(
            new DhlVersionSoapRequestHandler(self::API_VERSION_MAJOR, self::API_VERSION_MINOR),
            new AntiCompressionSoapRequestHandler(),
            new SoapRequestLoggingHandler($soapClient, $this->dhlRequestLogger, new DhlHttpSanitizer())
        );

        return $dhlApiClient;
    }
}
