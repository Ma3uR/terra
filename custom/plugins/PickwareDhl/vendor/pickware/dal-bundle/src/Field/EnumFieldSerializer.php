<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle\Field;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidSerializerFieldException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\StringFieldSerializer;
use Symfony\Component\Validator\Constraints\Choice;

class EnumFieldSerializer extends StringFieldSerializer
{
    protected function getConstraints(Field $field): array
    {
        if (!($field instanceof EnumField)) {
            throw new InvalidSerializerFieldException(EnumField::class, $field);
        }

        $constraints = parent::getConstraints($field);

        $constraints[] = new Choice([
            'multiple' => false,
            'choices' => $field->getAllowedValues(),
        ]);

        return $constraints;
    }
}
