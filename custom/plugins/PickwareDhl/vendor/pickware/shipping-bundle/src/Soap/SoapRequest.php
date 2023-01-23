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

class SoapRequest
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $body;

    /**
     * @param string $method
     * @param array $body
     */
    public function __construct(string $method, array $body)
    {
        $this->method = $method;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }
}
