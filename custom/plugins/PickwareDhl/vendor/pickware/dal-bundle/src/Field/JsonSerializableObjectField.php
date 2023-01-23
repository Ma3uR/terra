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

use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;

class JsonSerializableObjectField extends JsonField
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var callable
     */
    private $deserializer;

    /**
     * @param string $storageName
     * @param string $propertyName
     * @param string $class Classname of json-serializable class
     * @param callable|null $deserializer
     */
    public function __construct(string $storageName, string $propertyName, string $class, callable $deserializer = null)
    {
        parent::__construct($storageName, $propertyName);

        if ($deserializer === null) {
            $deserializer = [
                $class,
                'fromArray',
            ];
        }
        $this->class = $class;
        $this->deserializer = $deserializer;
    }

    /**
     * @return string
     */
    protected function getSerializerClass(): string
    {
        return JsonSerializableObjectFieldSerializer::class;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return callable
     */
    public function getDeserializer(): callable
    {
        return $this->deserializer;
    }
}
