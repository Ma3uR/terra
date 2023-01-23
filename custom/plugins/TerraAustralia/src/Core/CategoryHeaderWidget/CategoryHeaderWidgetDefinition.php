<?php declare(strict_types=1);

namespace TerraAustralia\Core\CategoryHeaderWidget;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
/*
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;*/

class CategoryHeaderWidgetDefinition extends EntityDefinition
{
    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return 'tr_category_header_widget';
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return CategoryHeaderWidgetEntity::class;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        return CategoryHeaderWidgetCollection::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            (new LongTextField('source', 'source')),
            (new FkField('category_id', 'categoryId', CategoryDefinition::class))->addFlags(new Required()),
        ]);
    }
}
