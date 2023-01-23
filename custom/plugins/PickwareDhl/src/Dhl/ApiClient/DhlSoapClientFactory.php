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

use SoapClient;
use SoapHeader;

class DhlSoapClientFactory
{
    private const PRODUCTION_ENDPOINT = 'https://cig.dhl.de/services/production/soap';
    private const PRODUCTION_USER = 'pickware_dhl_1';
    private const PRODUCTION_PASSWORD = 'lPpUHO1at2pIxSLwyVYszZ21rCHN6H';

    private const TEST_ENDPOINT = 'https://cig.dhl.de/services/sandbox/soap';
    private const TEST_USER = '2222222222_01';
    private const TEST_PASSWORD = 'pass';

    public function createDhlSoapClient(DhlApiClientConfig $dhlApiConfig, string $apiVersion): SoapClient
    {
        $wsdlFileName = sprintf(
            '%s/WsdlDocuments/gkp/%s/geschaeftskundenversand-api-%s.wsdl',
            __DIR__,
            $apiVersion,
            $apiVersion
        );

        $soapClient = new SoapClient($wsdlFileName, $this->getOptions($dhlApiConfig));
        $soapClient->__setSoapHeaders($this->getHeaders($dhlApiConfig));

        return $soapClient;
    }

    private function getOptions(DhlApiClientConfig $dhlApiConfig): array
    {
        $options = [
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
        ];

        if ($dhlApiConfig->shouldUseTestingEndpoint()) {
            // Use the DHL developer portal login of the config
            $extraOptions = [
                'login' => $dhlApiConfig->getUsername(),
                'password' => $dhlApiConfig->getPassword(),
                'location' => self::TEST_ENDPOINT,
            ];
        } else {
            // Production settings
            $extraOptions = [
                'login' => self::PRODUCTION_USER,
                'password' => self::PRODUCTION_PASSWORD,
                'location' => self::PRODUCTION_ENDPOINT,
            ];
        }

        return array_merge($options, $extraOptions);
    }

    /**
     * @param DhlApiClientConfig $dhlApiConfig
     * @return SoapHeader[]
     */
    private function getHeaders(DhlApiClientConfig $dhlApiConfig): array
    {
        if ($dhlApiConfig->shouldUseTestingEndpoint()) {
            $username = self::TEST_USER;
            $password = self::TEST_PASSWORD;
        } else {
            // DHL BCP API accepts username in lowercase format only
            $username = mb_strtolower($dhlApiConfig->getUsername());
            $password = $dhlApiConfig->getPassword();
        }

        $auth = [
            'user' => $username,
            'signature' => $password,
            'type' => 0,
        ];
        $authHeader = new SoapHeader('http://dhl.de/webservice/cisbase', 'Authentification', $auth, false);

        return [
            $authHeader
        ];
    }
}
