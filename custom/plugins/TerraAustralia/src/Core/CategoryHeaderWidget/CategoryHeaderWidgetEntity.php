<?php declare(strict_types=1);

namespace TerraAustralia\Core\CategoryHeaderWidget;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;

class CategoryHeaderWidgetEntity extends Entity
{
    use EntityIdTrait;

    protected $categoryId;
    
    public function getApiAlias(): string
    {
        return 'tr_category_header_widget';
    }
    
    public function getCategoryId()
    {
        return $this->categoryId;
    }
    
    public function getSource()
    {
        return json_decode($this->source, true);
    }
    
    public function getFieldConfig(): FieldConfigCollection
    {
        $collection = new FieldConfigCollection();
        $config = json_decode($this->source, true);
        
        foreach ($config as $key => $value) {
            $collection->add(
                new FieldConfig($key, 'static', $value)
            );
        }

        return $collection;
    }
    
}
