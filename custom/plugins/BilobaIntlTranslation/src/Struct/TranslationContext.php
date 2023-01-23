<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Struct;

use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Context;

class TranslationContext extends Struct implements \JsonSerializable {

    /**
     * @var array
     */
    private $pluginConfig;

	/**
	 * @var string
	 */
	private $api;

	/**
	 * @var LanguageEntity
	 */
	private $sourceLanguage;

	/**
	 * @var LanguageEntity
	 */
	private $targetLanguage;

    /**
     * @var string
     */
    private $entityType;

    /** 
     * @var string
     */
    private $entityId;

    /** 
     * @var string
     */
    private $initiator;

    /**
     * @var Context
     */
    private $shopwareContext;

	public function __construct(array $pluginConfig=null, 
                                string $api=null, 
                                LanguageEntity $targetLanguage=null, 
                                LanguageEntity $sourceLanguage=null, 
                                string $entityType=null, 
                                string $entityId=null,
                                string $initiator=null,
                                Context $shopwareContext=null){
        $this->pluginConfig = $pluginConfig;
		$this->api = $api;
		$this->targetLanguage = $targetLanguage;
		$this->sourceLanguage = $sourceLanguage;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->initiator = $initiator;
	    $this->shopwareContext = $shopwareContext;
    }

    /**
     * @return string
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param string $api
     *
     * @return self
     */
    public function setApi($api): string
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @return LanguageEntity
     */
    public function getSourceLanguage(): ?LanguageEntity
    {
        return $this->sourceLanguage;
    }


    /**
     * @return string
     */
    public function getSourceLanguageShort(): string
    {   

        /** @var Shopware\Core\System\Locale\LocaleEntity */
        $locale = $this->sourceLanguage->getLocale();
        
        list($language, $area) = explode('-', $locale->getCode());
        return $language;
    }
    
    /**
     * @param LanguageEntity $sourceLanguage
     *
     * @return self
     */
    public function setSourceLanguage(LanguageEntity $sourceLanguage)
    {
        $this->sourceLanguage = $sourceLanguage;

        return $this;
    }

    /**
     * @return LanguageEntity
     */
    public function getTargetLanguage(): LanguageEntity
    {
        return $this->targetLanguage;
    }

    /**
     * @return string
     */
    public function getTargetLanguageShort(): string
    {   

        /** @var Shopware\Core\System\Locale\LocaleEntity */
        $locale = $this->targetLanguage->getLocale();
        list($language, $area) = explode('-', $locale->getCode());

        return $language;
    }

    /**
     * @param LanguageEntity $targetLanguage
     *
     * @return self
     */
    public function setTargetLanguage(LanguageEntity $targetLanguage)
    {
        $this->targetLanguage = $targetLanguage;

        return $this;
    }

    /**
     * @return array
     */
    public function getPluginConfig(): array
    {
        return $this->pluginConfig;
    }

    /**
     * @param array $pluginConfig
     *
     * @return self
     */
    public function setPluginConfig(array $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;

        return $this;
    }

    /**
     * Checks wether the given config option is set or not.
     * 
     * @param string $key
     * @return boolean
     */
    public function hasPluginConfigOption(string $key): boolean
    {
        return isset($this->pluginConfig[$key]);
    }

    /**
     * Returns the config option.
     * 
     * @param string $key
     * @param mixed $default=null
     * @return mixed
     */
    public function getPluginConfigOption(string $key, $default=null)
    {
        return ($this->pluginConfig != null && isset($this->pluginConfig[$key])) ? $this->pluginConfig[$key] : $default;
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @param string $entityType
     *
     * @return self
     */
    public function setEntityType(string $entityType)
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     *
     * @return self
     */
    public function setEntityId(string $entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    /**
     * @param string $initiator
     *
     * @return self
     */
    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'initiator' => $this->initiator,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
            'api' => $this->api,
            'sourceLanguage' => $this->sourceLanguage ? $this->sourceLanguage->getId() : null,
            'targetLanguage' => $this->targetLanguage ? $this->targetLanguage->getId() : null
        ];
    }

    /**
     * @return Context
     */
    public function getShopwareContext()
    {
        return ($this->shopwareContext ?: Context::createDefaultContext());
    }

    /**
     * @param Context $shopwareContext
     *
     * @return self
     */
    public function setShopwareContext(Context $shopwareContext)
    {
        $this->shopwareContext = $shopwareContext;

        return $this;
    }
}