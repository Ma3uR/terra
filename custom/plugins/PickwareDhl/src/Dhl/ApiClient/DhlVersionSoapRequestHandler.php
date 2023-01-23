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

use Pickware\ShippingBundle\Soap\SoapRequest;
use Pickware\ShippingBundle\Soap\SoapRequestHandler;
use stdClass;

class DhlVersionSoapRequestHandler implements SoapRequestHandler
{
    /**
     * @var int
     */
    private $major;

    /**
     * @var int
     */
    private $minor;

    public function __construct(int $major, int $minor)
    {
        $this->major = $major;
        $this->minor = $minor;
    }

    public function handle(SoapRequest $soapRequest, callable $next): stdClass
    {
        $body = $soapRequest->getBody();
        $body['Version'] = [
            'majorRelease' => $this->major,
            'minorRelease' => $this->minor,
        ];

        return $next(new SoapRequest($soapRequest->getMethod(), $body));
    }
}
