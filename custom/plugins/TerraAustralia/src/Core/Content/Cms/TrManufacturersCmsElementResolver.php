<?php declare(strict_types=1);

namespace TerraAustralia\Core\Content\Cms;

use Psr\Container\ContainerInterface;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use TerraAustralia\Struct\TrManufacturersStruct;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class TrManufacturersCmsElementResolver extends AbstractCmsElementResolver
{
    private const TR_E_MANUFACTURERS_ENTITY_FALLBACK = 'tr-e-manufacturers-entity-fallback';
    
    private const STATIC_SEARCH_KEY = 'tr-e-manufacturers';
    
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
        return 'tr-e-manufacturers';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $collection = new CriteriaCollection();

        if (!$manufacturers = $config->get('manufacturers')) {
            return null;
        }

        if ($manufacturers->isStatic() && $manufacturers->getValue()) {
            $criteria = new Criteria($manufacturers->getValue());
            $criteria->addAssociation('media');
            $collection->add(self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(), ProductManufacturerDefinition::class, $criteria);
        }

        if ($manufacturers->isMapped() && $manufacturers->getValue() && $resolverContext instanceof EntityResolverContext) {
            if ($criteria = $this->collectByEntity($resolverContext, $products)) {
                $collection->add(self::TR_E_MANUFACTURERS_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), ProductManufacturerDefinition::class, $criteria);
            }
        }

        return $collection->all() ? $collection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $trManufacturers = new TrManufacturersStruct();
        $slot->setData($trManufacturers);

        if (!$manufacturerConfig = $config->get('manufacturers')) {
            return;
        }

        if ($manufacturerConfig->isStatic()) {
            $this->enrichFromSearch($trManufacturers, $result, self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier());
        }

        if ($manufacturerConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            
            $manufacturers = $this->resolveEntityValue($resolverContext->getEntity(), $manufacturerConfig->getValue());
            
            if (!$manufacturers) {
                $this->enrichFromSearch($trManufacturers, $result, self::TR_E_MANUFACTURERS_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier());
            } else {
                $trManufacturers->setProducts($manufacturers);
            }
        }
        
    }

    private function enrichFromSearch(TrManufacturersStruct $trManufacturers, ElementDataCollection $result, string $searchKey): void
    {
        $searchResult = $result->get($searchKey);
        if (!$searchResult) {
            return;
        }

        /** @var ProductManufacturerCollection|null $manufacturers */
        $manufacturers = $searchResult->getEntities();
        if (!$manufacturers) {
            return;
        }

        $trManufacturers->setManufacturers($manufacturers);
    }

    private function collectByEntity(EntityResolverContext $resolverContext, FieldConfig $config): ?Criteria
    {
        $entityManufacturers = $this->resolveEntityValue($resolverContext->getEntity(), $config->getValue());
        if ($entityManufacturers) {
            return null;
        }

        $criteria = $this->resolveCriteriaForLazyLoadedRelations($resolverContext, $config);
        $criteria->addAssociation('media');

        return $criteria;
    }

}
