<?php

namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Config\SystemConfigurationRepository;
use Crsw\CleverReachOfficial\Entity\Currency\CurrencyRepository;
use Crsw\CleverReachOfficial\Entity\Language\LanguageRepository;
use Crsw\CleverReachOfficial\Entity\Product\ProductRepository;
use Crsw\CleverReachOfficial\Entity\ProductTranslation\ProductTranslationRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\SalesChannelRepository;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ProductSearchController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class ProductSearchController extends AbstractController
{
    private const PRODUCT_QUERY_KEY = 'searchTerm';
    private const LANGUAGE_QUERY_KEY = 'searchLang';

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductTranslationRepository
     */
    private $productTranslationRepository;
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var SystemConfigurationRepository
     */
    private $systemConfigRepository;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * ProductSearchController constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductTranslationRepository $productTranslationRepository
     * @param LanguageRepository $languageRepository
     * @param CurrencyRepository $currencyRepository
     * @param UrlGeneratorInterface $urlGenerator
     * @param SystemConfigurationRepository $systemConfigRepository
     * @param SalesChannelRepository $salesChannelRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductTranslationRepository $productTranslationRepository,
        LanguageRepository $languageRepository,
        CurrencyRepository $currencyRepository,
        UrlGeneratorInterface $urlGenerator,
        SystemConfigurationRepository $systemConfigRepository,
        SalesChannelRepository $salesChannelRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->languageRepository = $languageRepository;
        $this->currencyRepository = $currencyRepository;
        $this->urlGenerator = $urlGenerator;
        $this->systemConfigRepository = $systemConfigRepository;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="cleverreach/search", name="cleverreach.search", defaults={"csrf_protected"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     * @return Response
     * @throws InconsistentCriteriaIdsException
     */
    public function search(Request $request): Response
    {
        $this->getConfigService()->setShopwareContext(Context::createDefaultContext());

        $data = [];
        if ($request->get('get') === 'filter') {
            $data = $this->getFilters();
        }

        if ($request->get('get') === 'search') {
            $data = $this->getSearchItems($request->get(self::PRODUCT_QUERY_KEY), $request->get(self::LANGUAGE_QUERY_KEY));
        }

        return new JsonApiResponse($data, 200);
    }

    /**
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function getFilters(): array
    {
        $input = [
            'name' => 'Product ID or name',
            'description' => 'Search by product name or number',
            'required' => true,
            'query_key' => self::PRODUCT_QUERY_KEY,
            'type' => 'input',
        ];

        $language = [
            'name' => 'Language',
            'description' => 'Select product description language',
            'required' => true,
            'query_key' => self::LANGUAGE_QUERY_KEY,
            'type' => 'dropdown',
            'values' => $this->getLanguages(),
        ];

        return [$input, $language];
    }

    /**
     * @param string $searchTerm
     * @param string $languageId
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function getSearchItems(string $searchTerm, string $languageId): array
    {
        $results = [];
        $results['settings'] = [
            'type' => 'product',
            'link_editable' => false,
            'link_text_editable' => true,
            'image_size_editable' => false,
        ];

        $results['items'] = $this->formatProducts($searchTerm ,$languageId);

        return $results;

    }

    /**
     * @param string $searchTerm
     * @param string $languageId
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function formatProducts(string $searchTerm, string $languageId): array
    {
        $results = [];
        $productsByNumber = $this->productRepository->getProductsByNumber(
            $searchTerm,
            $this->getConfigService()->getShopwareContext()
        );
        /** @var ProductEntity $productEntity */
        foreach ($productsByNumber as $productEntity) {
            $results[] = $this->formatItemForSearchByNumber($productEntity, $languageId);
        }

        $productTranslations = $this->productTranslationRepository->getProductsTranslationsByName(
            $searchTerm,
            $languageId,
            $this->getConfigService()->getShopwareContext()
        );
        /** @var ProductTranslationEntity $productTranslationEntity */
        foreach ($productTranslations as $productTranslationEntity) {
            $productEntity = $this->productRepository->getProductById(
                $productTranslationEntity->getProductId(),
                $this->getConfigService()->getShopwareContext()
            );
            if ($productEntity) {
                $results[] = $this->formatItemForSimpleProducts($productEntity, $productTranslationEntity);
            }
        }

        return $results;
    }

    /**
     * @param ProductEntity $productEntity
     * @param string $languageId
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function formatItemForSearchByNumber(ProductEntity $productEntity, string $languageId): array
    {
        $productTranslations = $productEntity->getTranslations();
        $productTranslation = null;
        if ($productTranslations) {
            $productTranslation = $productTranslations->filterByLanguageId($languageId)->first();
        }

        if (!$productEntity->getParentId()) {
            return $this->formatItemForSimpleProducts($productEntity, $productTranslation);
        }

        $parent = $this->productRepository->getProductById(
            $productEntity->getParentId(),
            $this->getConfigService()->getShopwareContext()
        );
        $parentTranslations = $parent ? $parent->getTranslations() : null;
        $parentTranslation = $parentTranslations ? $parentTranslations->filterByLanguageId($languageId)->first() : null;

        $title = $this->getTitleForVariantProduct($productEntity, $languageId, $parentTranslation, $parent);
        $desc = $parentTranslation ? $parentTranslation->getDescription() : $parent->getDescription();
        $priceCollection = $productEntity->getPrice() ?: $parent->getPrice();
        $price = $priceCollection ? $priceCollection->first() : null;
        $priceFormatted = $this->getPrice($price);
        $url = $this->getProductUrl($productEntity);
        $imgUrl = $this->getImageUrl($parent);

        return $this->getData($title, $desc, $priceFormatted, $url, $imgUrl);
    }

    /**
     * @param ProductEntity $productEntity
     * @param ProductTranslationEntity|null $productTranslationEntity
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function formatItemForSimpleProducts(ProductEntity $productEntity, ?ProductTranslationEntity $productTranslationEntity): array
    {
        $title = $productTranslationEntity ? (string)$productTranslationEntity->getName() : (string)$productEntity->getName();
        $desc = $productTranslationEntity ? (string)$productTranslationEntity->getDescription() : (string)$productEntity->getDescription();
        $price = $productEntity->getPrice() ? $productEntity->getPrice()->first() : '';
        $priceFormatted = $this->getPrice($price);
        $url = $this->getProductUrl($productEntity);
        $imgUrl = $this->getImageUrl($productEntity);

        return $this->getData($title, $desc, $priceFormatted, $url, $imgUrl);
    }

    /**
     * Returns image url
     *
     * @param ProductEntity $productEntity
     *
     * @return string
     */
    private function getImageUrl(ProductEntity $productEntity): string
    {
        $productMediaCollection = $productEntity->getMedia();
        if ($productMediaCollection && $productMediaCollection->first()) {
            $media = $productMediaCollection->first()->getMedia();
            if ($media) {
                return $media->getUrl();
            }
        }

        return '';
    }

    /**
     * Returns product url
     *
     * @param ProductEntity $productEntity
     *
     * @return string
     */
    private function getProductUrl(ProductEntity $productEntity): string
    {
        return $this->urlGenerator->generate('frontend.detail.page', ['productId' => $productEntity->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function getLanguages(): array
    {
        $context = $this->getConfigService()->getShopwareContext();

        $languages = $this->languageRepository->getLanguages($context);
        $languageMap = [];
        $defaultShopName = $this->systemConfigRepository->getDefaultShopName($context);
        $defaultShop = $this->salesChannelRepository->getSalesChannelByShopName($defaultShopName, $context);

        /** @var LanguageEntity $language */
        foreach ($languages as $language) {
            if ($defaultShop && $defaultShop->getLanguageId() === $language->getId()) {
                $preselected = ['value' => $language->getId(), 'text' => $language->getName()];
            } else {
                $languageMap[] = ['value' => $language->getId(), 'text' => $language->getName()];
            }
        }

        if (isset($preselected)) {
            array_unshift($languageMap, $preselected);
        }

        return $languageMap;
    }

    /**
     * Returns data for response
     *
     * @param string $title
     * @param string $desc
     * @param string $priceFormatted
     *
     * @param string $url
     * @param string $imgUrl
     *
     * @return array
     */
    private function getData(string $title, string $desc, string $priceFormatted, string $url, string $imgUrl): array
    {
        return [
            'title' => $title,
            'description' => strip_tags($desc),
            'content' => $desc,
            'price' => $priceFormatted,
            'url' => $url,
            'image' => $imgUrl,
        ];
    }

    /**
     * Returns price formatted
     *
     * @param Price|null $price
     *
     * @return string
     * @throws InconsistentCriteriaIdsException
     */
    private function getPrice(?Price $price): string
    {
        $priceFormatted = '';
        if ($price) {
            $priceFormatted = $price->getGross();
            $currency = $this->currencyRepository->getCurrencyById(
                $price->getCurrencyId(),
                $this->getConfigService()->getShopwareContext()
            );
            if ($currency) {
                $priceFormatted = $currency->getIsoCode() . ' ' . $priceFormatted;
            }
        }

        return (string)$priceFormatted;
    }

    /**
     * Returns title for variant product
     *
     * @param ProductEntity $productEntity
     * @param string $languageId
     * @param ProductTranslationEntity|null $parentTranslation
     * @param ProductEntity|null $parent
     *
     * @return string
     */
    private function getTitleForVariantProduct(
        ProductEntity $productEntity,
        string $languageId,
        ?ProductTranslationEntity $parentTranslation,
        ?ProductEntity $parent
    ): string {
        if (!$parent) {
            return  '';
        }

        $title = $parentTranslation ? $parentTranslation->getName() : $parent->getName();
        $options = $productEntity->getOptions();
        /** @var PropertyGroupOptionEntity $option */
        foreach ($options as $option) {
            $title .= ' ';
            $optionsTranslations = $option->getTranslations();
            $optionsTranslation = $optionsTranslations ? $optionsTranslations->filterByLanguageId($languageId) : null;
            $title .= $optionsTranslation ? $optionsTranslation->get('name') : $option->getName();
        }

        return (string)$title;
    }

    /**
     * Returns an instance of config service.
     *
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}