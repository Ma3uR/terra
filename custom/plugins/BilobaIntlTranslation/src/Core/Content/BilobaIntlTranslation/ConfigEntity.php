<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Core\Content\BilobaIntlTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageEntity;

class ConfigEntity extends Entity implements \JsonSerializable
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $translationApi;

    /**
     * @var Language
     */
    protected $targetLanguage;

    /**
     * @var Language
     */
    protected $sourceLanguage;

    /**
     * @return string
     */
    public function getTranslationApi(): string
    {
        return $this->translationApi;
    }

    /**
     * @param string $translationApi
     *
     * @return self
     */
    public function setTranslationApi($translationApi)
    {
        $this->translationApi = $translationApi;

        return $this;
    }

    /**
     * @return Language
     */
    public function getTargetLanguage(): ?LanguageEntity
    {
        return $this->targetLanguage;
    }

    /**
     * @param Language $targetLanguage
     *
     * @return self
     */
    public function setTargetLanguage(LanguageEntity $targetLanguage)
    {
        $this->targetLanguage = $targetLanguage;

        return $this;
    }

    /**
     * @return Language
     */
    public function getSourceLanguage(): ?LanguageEntity
    {
        return $this->sourceLanguage;
    }

    /**
     * @param Language $sourceLanguage
     *
     * @return self
     */
    public function setSourceLanguage(LanguageEntity $sourceLanguage)
    {
        $this->sourceLanguage = $sourceLanguage;

        return $this;
    }
}