<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Soap;

use SoapClient;
use stdClass;

class SoapApiClient
{
    /**
     * @var SoapRequestHandler[]
     */
    private $requestHandlers = [];

    /**
     * @var SoapClient
     */
    private $soapClient;

    /**
     * @param SoapClient $soapClient
     */
    public function __construct(SoapClient $soapClient)
    {
        $this->soapClient = $soapClient;
    }

    public function sendRequest(SoapRequest $request): stdClass
    {
        return $this->pipeThroughRequestHandlers($request, $this->requestHandlers);
    }

    public function use(SoapRequestHandler ...$requestHandlers): void
    {
        $this->requestHandlers = array_merge($this->requestHandlers, $requestHandlers);
    }

    /**
     * @param SoapRequest $request
     * @param SoapRequestHandler[] $requestHandlers
     * @return stdClass
     */
    private function pipeThroughRequestHandlers(SoapRequest $request, array $requestHandlers): stdClass
    {
        $nextRequestHandler = array_shift($requestHandlers);

        if ($nextRequestHandler === null) {
            return $this->soapClient->__soapCall($request->getMethod(), ['parameters' => $request->getBody()]);
        }

        $next = function (SoapRequest $request) use ($requestHandlers): stdClass {
            return $this->pipeThroughRequestHandlers($request, $requestHandlers);
        };

        return $nextRequestHandler->handle($request, $next);
    }
}
