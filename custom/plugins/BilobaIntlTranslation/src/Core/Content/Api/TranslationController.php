<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Core\Content\Api;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Psr\Log\LoggerInterface;
use Biloba\IntlTranslation\Service\TranslatorService;
use Biloba\IntlTranslation\Struct\TranslationContext;
use Biloba\IntlTranslation\Components\TranslationApi;
use Biloba\IntlTranslation\Components\BilobaIntlTranslationException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\Language\LanguageEntity;
use Biloba\IntlTranslation\Components\TranslationApi\Exceptions\RequestTimeoutException;

/**
 * @RouteScope(scopes={"api"})
 */
class TranslationController {

    /**
     * @var mixed
     */
    private $container;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var TranslatorService
     */
    private $translatorService;

    public function __construct($container, SystemConfigService $systemConfigService, TranslatorService $translatorService) {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
        $this->translatorService = $translatorService;
    }

    /**
     * @Route("/api/v{version}/_action/biloba-intl-translation/get-config", name="api.action.biloba-intl-translation.get.config", methods={"POST"})
     * 1. Method for loading config object depending on targetlangId
     * @return JsonResponse
     */
    public function getConfigObject(Request $request)
    {
        $languageId = $request->get('languageId');
        $config = $this->loadConfig($languageId);

        if($config && $config->getTargetLanguage()) {
            list($language, $area) = explode('-', $config->getTargetLanguage()->getLocale()->getCode());

            if(!$this->translatorService->isLanguageSupported($language)) {
                $config = null;
            }
        }

        return new JsonResponse($config);
    }

    /**
     * @Route("/api/v{version}/_action/biloba-intl-translation-translation", name="api.action.biloba-intl-translation.translation.translate", methods={"POST"})
     * 
     * @return JsonResponse
     */
    public function translate(Request $request, Context $context)
    {
        $entityType = $request->get('entity');
        
        if($entityType == 'mail_template') {
            $entityId = $request->get(lcfirst(str_replace('_', '', ucwords($entityType, '_'))) . 'Id');
        }
        else {
            $entityId = $request->get('entityId');
        }
        
        $languageId = $request->get('languageId');

        // load plugin config
        $pluginConfig = $this->systemConfigService->get('BilobaIntlTranslation.config');

        // load the translation config
        $config = $this->loadConfig($languageId);
        
        // load language objects
        $targetLanguage = $config->getTargetLanguage();
        $sourceLanguage = $config->getSourceLanguage();
        if(!$sourceLanguage) {
            $sourceLanguage = $this->getDefaultLanguage();
        }

        // update translator service context
        $this->translatorService->setContext(new TranslationContext(
            $pluginConfig,
            $config->getTranslationApi(),
            $targetLanguage,
            $sourceLanguage,
            $entityType,
            $entityId,
            'BilobaIntlTranslation',
            $context
        ));
   
        try {
            $translatedFields = $this->translatorService->translate($entityType, $entityId);
        } catch(BilobaIntlTranslationException $ex) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $ex->getMessage(),
                'errorCode' => $ex->getErrorCode()
            ]);
        }

        return new JsonResponse($translatedFields);
    }

    /**
     * @Route("/api/v{version}/_action/biloba-intl-translation/is-language-supported", name="api.action.biloba-intl-translation.translation.get_languages", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function isLanguageSupported(Request $request, Context $context)
    {
        $languageId = $request->get('languageId');
        $languageEntity = $this->getSystemLanguageById($languageId);

        list($language, $area) = explode('-', $languageEntity->getLocale()->getCode());
        $localeCode = $languageEntity->getLocale()->getCode();

        // load plugin config
        $pluginConfig = $this->systemConfigService->get('BilobaIntlTranslation.config');

        // update translator service context
        $this->translatorService->setContext(new TranslationContext(
            $pluginConfig
        ));

        $supportedLanguages = $this->translatorService->getSupportedLanguages();

        return new JsonResponse(['supported' => (in_array($language, $supportedLanguages) || in_array($localeCode, $supportedLanguages))]);
    }

    private function getSystemLanguageById($id): ?LanguageEntity {

        /** @var EntityRepositoryInterface $productRepository */
        $repository = $this->container->get('language.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $criteria->addAssociation('locale');
        
        /** @var EntitySearchResult $products */
        $searchResult = $repository->search($criteria, Context::createDefaultContext());

        if($searchResult->getTotal() > 0) {
            return $searchResult->first();
        }

        return null;
    }


    /**
     * @Route("/api/v{version}/_action/biloba-intl-translation-translation/get-translation-apis", name="api.action.biloba-intl-translation.translation-api", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function getApis(Request $request, Context $context)
    {
        $language = null;
        $languageId = $request->get('languageId');
        if($languageId) {
            $languageEntity = $this->getSystemLanguageById($languageId);
            $localeCode = $languageEntity->getLocale()->getCode();
            list($language, $area) = explode('-', $localeCode);
        }

        // load plugin config
        $pluginConfig = $this->systemConfigService->get('BilobaIntlTranslation.config');

        // update translator service context
        $this->translatorService->setContext(new TranslationContext(
            $pluginConfig
        ));

        $apiList = [];
        foreach($this->translatorService->getTranslationApis() as $api) {
            if($language == null || $api->isLanguageSupported($language) || $api->isLanguageSupported($localeCode)) {
                $apiList[] = ['value' => $api->getIdentifier(), 'label' => $api->getLabel()];
            }
        }
        
        return new JsonResponse($apiList);
    }

    private function getEntityById($entity, $id) {
        /** @var EntityRepositoryInterface $productRepository */
        $repository = $this->container->get($entity . '.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $criteria->addAssociation('translations');    
        
        /** @var EntitySearchResult $products */
        $searchResult = $repository->search($criteria, Context::createDefaultContext());
        //var_dump($searchResult->getTotal());

        return $searchResult->first();
    }

    private function loadConfig($targetLanguageId) {
        /** @var EntityRepositoryInterface $productRepository */
        $repository = $this->container->get('biloba_intl_translation_config.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('targetLanguageId', $targetLanguageId));
        $criteria->addAssociation('targetLanguage');    
        $criteria->addAssociation('sourceLanguage');    
        $criteria->addAssociation('targetLanguage.locale');    
        $criteria->addAssociation('sourceLanguage.locale');  
        
        /** @var EntitySearchResult $products */
        $searchResult = $repository->search($criteria, Context::createDefaultContext());

        if($searchResult->getTotal() > 0) {
            return $searchResult->first();
        }

        return null;
    }

    private function getDefaultLanguage() {
        /** @todo Load real default language! */

        /** @var EntityRepositoryInterface $productRepository */
        $repository = $this->container->get('language.repository');

        $criteria = new Criteria();
        
        /** @var EntitySearchResult $products */
        $searchResult = $repository->search($criteria, Context::createDefaultContext());

        if($searchResult->getTotal() > 0) {
            return $searchResult->first();
        }

        return null;
    }

    /**
     * @Route("/api/v{version}/_action/biloba-intl-translation-checkApi", name="checkApi", methods={"POST"})
     * 
     * @return JsonResponse
     */
    public function checkApi(Request $request) {
        $apiIdentifier = $request->get('identifier');

        // load plugin config
        $pluginConfig = $this->systemConfigService->get('BilobaIntlTranslation.config');

        // setup translation context
        $this->translatorService->setContext(new TranslationContext(
            $pluginConfig,
            $apiIdentifier
        ));

        // get translation api 
        $translationApi = $this->translatorService->getTranslationApiByIdentifier($apiIdentifier);

        // checking if we got a translation api
        if($translationApi) {

            // checking with placeholder method if api valid, currently google api return false and deppl true
            if($translationApi->isValid()) {
                return new JsonResponse([
                    'status' => 'ok'
                ]);
            }
        }
        // default return is json object with error status
        return new JsonResponse([
            'status' => 'error'
        ]);
    }
}