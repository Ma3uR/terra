<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle\ExceptionHandling;

use Exception;
use Shopware\Core\Framework\ShopwareHttpException;

/**
 * Class UniqueIndexHttpException
 *
 * Extends Shopware's ShopwareHttpException for DBAL unique index violation exceptions.
 */
class UniqueIndexHttpException extends ShopwareHttpException
{
    /**
     * @var string
     */
    private $errorCode;

    public function __construct(string $errorCode, string $message, array $parameters = [], ?\Throwable $e = null)
    {
        parent::__construct($message, $parameters, $e);

        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public static function create(
        UniqueIndexExceptionMapping $uniqueIndexExceptionMapping,
        array $valuesByField,
        Exception $previousException
    ): self {
        return new self(
            $uniqueIndexExceptionMapping->getErrorCodeToAssign(),
            self::createErrorDetail($uniqueIndexExceptionMapping, $valuesByField),
            [
                'index' => $uniqueIndexExceptionMapping->getUniqueIndexName(),
                'entity' => $uniqueIndexExceptionMapping->getEntityName(),
                'fields' => $valuesByField,
            ],
            $previousException
        );
    }

    private static function createErrorDetail(
        UniqueIndexExceptionMapping $uniqueIndexExceptionMapping,
        array $valuesByField
    ): string {
        $fieldValuePairs = [];
        foreach ($valuesByField as $field => $value) {
            $fieldValuePairs[] = vsprintf(
                '%s = %s',
                [
                    $field,
                    $value,
                ]
            );
        }

        return vsprintf(
            'Entity "%s" with (%s) already exists.',
            [
                $uniqueIndexExceptionMapping->getEntityName(),
                implode(', ', $fieldValuePairs),
            ]
        );
    }
}
