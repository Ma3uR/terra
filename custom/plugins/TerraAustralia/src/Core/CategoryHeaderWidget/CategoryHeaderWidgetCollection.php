<?php declare(strict_types=1);

namespace TerraAustralia\Core\CategoryHeaderWidget;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                            add(CategoryHeaderWidgetEntity $entity)
 * @method void                            set(string $key, CategoryHeaderWidgetEntity $entity)
 * @method CategoryHeaderWidgetEntity[]    getIterator()
 * @method CategoryHeaderWidgetEntity[]    getElements()
 * @method CategoryHeaderWidgetEntity|null get(string $key)
 * @method CategoryHeaderWidgetEntity|null first()
 * @method CategoryHeaderWidgetEntity|null last()
 */
class CategoryHeaderWidgetCollection extends EntityCollection
{

    protected function getExpectedClass(): string
    {
        return CategoryHeaderWidgetEntity::class;
    }
    
    public function getCategoriesIds(): array
    {
        return $this->fmap(function (CategoryHeaderWidgetEntity $entity) {
            return $entity->getCategoryId();
        });
    }
    
}
