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

use Doctrine\DBAL\DBALException;
use \Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;

class UniqueIndexExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var UniqueIndexExceptionMapping[]
     */
    private $uniqueIndexExceptionMappings;

    public function __construct(array $uniqueIndexExceptionMappings)
    {
        $this->uniqueIndexExceptionMappings = $uniqueIndexExceptionMappings;
    }

    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(Exception $exception, WriteCommand $command): ?Exception
    {
        if (!$exception instanceof DBALException) {
            return null;
        }

        foreach ($this->uniqueIndexExceptionMappings as $uniqueIndexExceptionMapping) {
            $indexViolationPattern = sprintf(
                '/SQLSTATE\\[23000\\]:.*1062 Duplicate entry .*%s.*/',
                $uniqueIndexExceptionMapping->getUniqueIndexName()
            );

            if (preg_match($indexViolationPattern, $exception->getMessage())) {
                $writeCommandPayload = $command->getPayload();
                $exceptionRelevantPayload = [];
                foreach ($uniqueIndexExceptionMapping->getFields() as $field) {
                    $formattedValue = $writeCommandPayload[$field];
                    if (is_string($formattedValue) && !preg_match('//u', $formattedValue)) {
                        // We assume the value is binary (i.e. a UUID) because it contains no unicode character.
                        // See https://stackoverflow.com/questions/25343508/detect-if-string-is-binary/65414958#65414958
                        $formattedValue = bin2hex($formattedValue);
                    }

                    $exceptionRelevantPayload[$field] = $formattedValue;
                }

                return UniqueIndexHttpException::create(
                    $uniqueIndexExceptionMapping,
                    $exceptionRelevantPayload,
                    $exception
                );
            }
        }

        return null;
    }
}
