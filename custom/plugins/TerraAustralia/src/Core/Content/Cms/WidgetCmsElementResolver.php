<?php declare(strict_types=1);

namespace TerraAustralia\Core\Content\Cms;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Category\CategoryDefinition;

class WidgetCmsElementResolver extends AbstractCmsElementResolver
{
    private const STATIC_SEARCH_KEY = 'tr-e-widget';
    
    /**
     * @var container
     */
    private $container;
    
    public function __construct(
        ContainerInterface $container
    )
    {
        $this->container = $container;
    }
    
    public function getType(): string
    {
        return 'tr-e-widget';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        
        $collection = new CriteriaCollection();
        
        if (!$type = $config->get('targetType')) {
            return null;
        }
        
        if (!$target = $config->get('target')) {
            return null;
        }
        
        if ($target->isStatic() && $target->getValue()) {
            
            $criteria = new Criteria([$target->getValue()]);
            $criteria->addAssociation('media');
            
            // always `category`
            $class = CategoryDefinition::class; //$type->getValue() == 'category' ? CategoryDefinition::class : CategoryDefinition::class;
            
            $collection->add(self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(), $class, $criteria);
        }
        
        return $collection->all() ? $collection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        
        $type = $config->get('targetType');
        
        if ( $type->getValue() == 'category' ) {
             $this->enrichCategory($slot, $resolverContext, $result);
        }
        
    }
    
    protected function enrichCategory(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        
        $resultsItems =$result->get(self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier());
        
        if( $resultsItems->getTotal() > 0 ) {
            $items = $resultsItems->getEntities()->getElements();
            
            $category = $resultsItems->getEntities()->first();
            
            $criteria = new Criteria();
            $criteria->addAssociation('media');
            
            $criteria->addFilter( new EqualsFilter('parentId', $category->getId()) );
            $criteria->setLimit(5);
            
            $childs = $this->container->get('category.repository')->search($criteria, Context::createDefaultContext() )->getEntities();
            
            $category->assign([
                'childs' => $childs->count() > 0 ? $childs->getElements() : null
            ]);
            
            $slot->setData($category);
        }
    }
    
}
