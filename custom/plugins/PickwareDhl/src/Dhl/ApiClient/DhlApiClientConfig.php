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

class DhlApiClientConfig
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $shouldUseTestingEndpoint;

    public function __construct(string $username, string $password, bool $shouldUseTestingEndpoint = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->shouldUseTestingEndpoint = $shouldUseTestingEndpoint;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function shouldUseTestingEndpoint(): bool
    {
        return $this->shouldUseTestingEndpoint;
    }
}
