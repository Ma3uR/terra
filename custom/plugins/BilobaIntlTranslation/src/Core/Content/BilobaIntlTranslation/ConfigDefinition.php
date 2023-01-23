<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Core\Content\BilobaIntlTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;

class ConfigDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'biloba_intl_translation_config';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            (new FkField('target_language_id', 'targetLanguageId', LanguageDefinition::class))->addFlags(new Required()),
            (new OneToOneAssociationField('targetLanguage', 'target_language_id', 'id', LanguageDefinition::class, false)),

            (new FkField('source_language_id', 'sourceLanguageId', LanguageDefinition::class)),
            (new OneToOneAssociationField('sourceLanguage', 'source_language_id', 'id', LanguageDefinition::class, false)),

            (new StringField('translation_api', 'translationApi'))->addFlags(new Required())
        ]);
    }
    
    public function getEntityClass(): string
    {
        return ConfigEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ConfigCollection::class;
    }
}