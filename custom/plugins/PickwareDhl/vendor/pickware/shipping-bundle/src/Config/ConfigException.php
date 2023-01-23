<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Config;

use Exception;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\HttpUtils\JsonApi\JsonApiErrorSerializable;

class ConfigException extends Exception implements JsonApiErrorSerializable
{
    public const ERROR_CODE_FIELD_MISSING = 'PICKWARE_SHIPPING__CONFIG__FIELD_MISSING';
    public const ERROR_CODE_FIELD_INVALID_FORMATTED = 'PICKWARE_SHIPPING__CONFIG__FIELD_INVALID_FORMATTED';

    /**
     * @var string
     */
    private $configDomain;

    /**
     * @var string
     */
    private $affectedField;

    /**
     * @var string
     */
    private $errorCode;

    public function __construct(string $message, string $configDomain, string $affectedField, string $errorCode)
    {
        parent::__construct($message);
        $this->configDomain = $configDomain;
        $this->affectedField = $affectedField;
        $this->errorCode = $errorCode;
    }

    public static function invalidFormattedField(string $configDomain, string $fieldName): self
    {
        return new self(
            sprintf(
                'The value of field "%s" in config domain "%s" has an invalid format.',
                $fieldName,
                $configDomain
            ),
            $configDomain,
            $fieldName,
            self::ERROR_CODE_FIELD_INVALID_FORMATTED
        );
    }

    public function getConfigDomain(): string
    {
        return $this->configDomain;
    }

    public function getAffectedField(): string
    {
        return $this->affectedField;
    }

    public static function missingConfigurationField(string $configDomain, string $missingField): self
    {
        return new self(
            sprintf(
                'The configuration for domain "%s" is missing following field: "%s".',
                $configDomain,
                $missingField
            ),
            $configDomain,
            $missingField,
            self::ERROR_CODE_FIELD_MISSING
        );
    }

    public function serializeToJsonApiError(): JsonApiError
    {
        return new JsonApiError([
            'code' => $this->errorCode,
            'detail' => $this->message,
            'meta' => [
                'configDomain' => $this->configDomain,
                'field' => $this->affectedField,
            ],
        ]);
    }
}
