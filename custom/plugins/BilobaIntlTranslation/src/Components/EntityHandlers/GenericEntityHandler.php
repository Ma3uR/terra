<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\EntityHandlers;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Biloba\IntlTranslation\Components\EntityHandlerInterface;
use Biloba\IntlTranslation\Struct\TranslatableFieldCollection;
use Biloba\IntlTranslation\Struct\TranslationContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GenericEntityHandler implements EntityHandlerInterface {
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var array
     */
    protected $entityFields;

    public function __construct(ContainerInterface $container, LoggerInterface $logger) {
        $this->container = $container;
        $this->log = $logger;
    }

    /** {@inheritdocs} */
    public function isSupported(string $name): bool
    {
        return in_array($name, $this->getSupportedEntites());
    }

    /** {@inheritdocs} */
    public function getSupportedEntites(): array
    {
        return array_keys($this->entityFields);
    }

    /** {@inheritdocs} */
    private function getTranslationContext(): TranslationContext
    {
        $translatorService = $this->container->get('biloba.intl_translation.translator');
        return $translatorService->getContext();
    }

    /** {@inheritdocs} */
    public function getFields(string $name): TranslatableFieldCollection
    {

        if(isset($this->entityFields[$name])) {

            $collection = new TranslatableFieldCollection();

            foreach($this->entityFields[$name] as $fieldName) {
                $collection->addNew($fieldName);
            }

            return $collection;
        }

        return new TranslatableFieldCollection();
    }

    /** {@inheritdocs} */
    public function getValues(string $type, string $id, TranslatableFieldCollection $fields): TranslatableFieldCollection
    {
        $translationContext = $this->getTranslationContext();
        $fieldsValues = new TranslatableFieldCollection();

        // load the entites & translations
        $entity = $this->getEntityById($type, $id);
        $translations = $entity->getTranslations();

        /** @var Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection [description] */
        $sourceTranslations = $translations->filterByLanguageId($translationContext->getSourceLanguage()->getId())->first();

        /** @var Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity */
        $targetTranslations = $translations->filterByLanguageId($translationContext->getTargetLanguage()->getId())->first();

        // if we don't have source translations abort here
        if(!$sourceTranslations) {
            return $fieldsValues;
        }

        foreach($fields as $field) {

            // check if we have no source value, if true skip this field
            $sourceValue = $sourceTranslations->get($field->getName());
            if(trim("".$sourceValue) == "") {
                continue;
            }

            // check if we have a target value, if true skipt this field
            if($targetTranslations) {
                $targetValue = $targetTranslations->get($field->getName());
                if(trim("".$targetValue) != "") {
                    continue;
                }
            }

            $fieldsValues->addNew($field->getName(), $sourceValue);
        }

        return $fieldsValues;
    }

    /** {@inheritdocs} */
    public function updateTranslations(string $type, string $id, array $translatedFields): bool
    {
        $translationContext = $this->getTranslationContext();
        $targetLanguage = $translationContext->getTargetLanguage();
        
        $translatedFields[lcfirst(str_replace('_', '', ucwords($type, '_'))) . 'Id'] = $id;
        $translatedFields['languageId'] = $targetLanguage->getId();

        /** @var Shopware\Core\Framework\DataAbstractionLayer\EntityRepository */
        $repository = $this->container->get($type . '_translation.repository');
        $repository->upsert([
            $translatedFields
        ], Context::createDefaultContext());

        return true;
    }

    /**
     * Loads a entity by its id with the translation association.
     * 
     * @param  string $type
     * @param  string $id
     * @return Entity
     */
    private function getEntityById(string $type, string $id) {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get($type . '.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $criteria->addAssociation('translations');    
        
        /** @var EntitySearchResult $searchResult */
        $searchResult = $repository->search($criteria, Context::createDefaultContext());

        /** @var Entity */
        return $searchResult->first();
    }
}