<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Core\Content\BilobaIntlTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;

class LogDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'biloba_intl_translation_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('initiator', 'initiator'))->addFlags(new Required()),
            (new IdField('entity_id', 'entityId'))->addFlags(new Required()),
            (new StringField('entity_type', 'entityType'))->addFlags(new Required()),
            (new StringField('type', 'type'))->addFlags(new Required()),
            (new StringField('status', 'status')),
            (new JsonField('context', 'context')),
            (new CreatedAtField()),
            (new UpdatedAtField())
        ]);
    }
    
    public function getEntityClass(): string
    {
        return LogEntity::class;
    }

    public function getCollectionClass(): string
    {
        return LogCollection::class;
    }
}