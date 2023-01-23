<?php declare(strict_types=1);

namespace TerraAustralia\Decorators;

use Shopware\Core\Content\Cms\DataAbstractionLayer\FieldSerializer\SlotConfigFieldSerializer as SlotConfigFieldSerializerOrigin;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;

class SlotConfigFieldSerializerDecorator extends SlotConfigFieldSerializerOrigin
{
    
    public const SOURCE_PRODUCT_STREAM = 'product_stream';
    
    protected function getConstraints(Field $field): array
    {
        
        return [
            new All([
                'constraints' => new Collection([
                    'allowExtraFields' => false,
                    'allowMissingFields' => false,
                    'fields' => [
                        'source' => [
                            new Choice(['choices' => [
                                FieldConfig::SOURCE_STATIC,
                                FieldConfig::SOURCE_MAPPED,
                                self::SOURCE_PRODUCT_STREAM,
                            ]]),
                            new NotBlank(),
                        ],
                        'value' => [],
                    ],
                ]),
            ]),
        ];
    }
}
