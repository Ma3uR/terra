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
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class JsonSerializableObjectFieldSerializer extends JsonFieldSerializer
{
    /**
     * @inheritDoc
     */
    public function decode(Field $field, $value)
    {
        if (!($field instanceof JsonSerializableObjectField)) {
            throw new InvalidSerializerFieldException(JsonSerializableObjectField::class, $field);
        }

        if ($value === null) {
            return null;
        }

        $value = parent::decode($field, $value);

        return $field->getDeserializer()($value);
    }

    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): \Generator
    {
        if (!$field instanceof JsonSerializableObjectField) {
            throw new InvalidSerializerFieldException(JsonField::class, $field);
        }

        if (!is_a($data->getValue(), $field->getClass())) {
            // In case the passed value isn't an instance of the expected class, it is assumed that the field value was
            // passed as encoded value (e.g. when it comes from the API). The decoding from encoded value to
            // object is then done on the fly here.
            $data = new KeyValuePair(
                $data->getKey(), // $key
                $field->getDeserializer()($data->getValue()), // $value
                $data->isRaw() // $isRaw
            );
        }

        yield from parent::encode($field, $existence, $data, $parameters);
    }

    /**
     * @inheritDoc
     */
    protected function getConstraints(Field $field): array
    {
        if (!($field instanceof JsonSerializableObjectField)) {
            throw new InvalidSerializerFieldException(JsonSerializableObjectField::class, $field);
        }

        return [
            new NotNull(),
        ];
    }
}
