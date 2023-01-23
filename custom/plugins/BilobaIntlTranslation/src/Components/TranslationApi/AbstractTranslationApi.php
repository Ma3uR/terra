<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\TranslationApi;

use Biloba\IntlTranslation\Struct\TranslationContext;
use Biloba\IntlTranslation\Components\TranslationApiInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractTranslationApi implements TranslationApiInterface {
	private $context;

	/**
	 * Set the translation context
	 * 
	 * @param  TranslationContext $context
	 */
    public function setContext(TranslationContext $context): void 
    {
        $this->context = $context;
    }

    /**
     * @return TranslationContext
     */
    public function getContext(): TranslationContext
    {
        return $this->context;
    }

    /**
     * Checks if the given string contains html or not
     * 
     * @param  string  $text
     * @return boolean
     */
	public function isHtml($text): bool
	{
		return strip_tags($text) != $text;
	}

	/**
	 * Translates the given string
	 * @param  string $input
	 * @return string
	 */
	abstract public function translateString(string $input): string;

	/**
	 * Returns a readable name for the API
	 * @return string
	 */
	abstract public function getLabel(): string;
	
	/**
	 * Returns a unique identifier for this API.
	 * @return string
	 */
	abstract public function getIdentifier(): string;
	
	/**
	 * @return boolean
	 */
	abstract public function isAvailable(): bool;

	/**
	 * @return array
	 */
	abstract public function getSupportedLanguages(): array;

	/**
	 * @return boolean
	 */
	abstract public function isValid(): bool;

	public function isLanguageSupported(string $language): bool
	{
		return in_array($language, $this->getSupportedLanguages());
	}
}