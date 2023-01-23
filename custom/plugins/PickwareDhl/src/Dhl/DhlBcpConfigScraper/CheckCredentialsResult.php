<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\DhlBcpConfigScraper;

use JsonSerializable;

class CheckCredentialsResult implements JsonSerializable
{
    /**
     * @var bool
     */
    private $areCredentialsValid;

    /**
     * The credentials can be valid (correct user name and password) but the user can still be a "systemuser"
     * (Systembenutzer). Such users can access the BCP API but not the BCP itself.
     *
     * @var null|bool
     */
    private $isSystemUser;

    private function __construct()
    {
    }

    public function jsonSerialize()
    {
        return [
            'areCredentialsValid' => $this->areCredentialsValid,
            'isSystemUser' => $this->isSystemUser,
        ];
    }

    public static function credentialsAreValid(): self
    {
        $self = new self();
        $self->areCredentialsValid = true;
        $self->isSystemUser = false;

        return $self;
    }

    public static function credentialsAreInvalid(): self
    {
        $self = new self();
        $self->areCredentialsValid = false;
        $self->isSystemUser = null;

        return $self;
    }

    public static function userIsSystemUser(): self
    {
        $self = new self();
        $self->areCredentialsValid = true;
        $self->isSystemUser = true;

        return $self;
    }

    public function areCredentialsValid(): bool
    {
        return $this->areCredentialsValid;
    }

    public function isSystemUser(): ?bool
    {
        return $this->isSystemUser;
    }
}
