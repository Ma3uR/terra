<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components;

use Biloba\IntlTranslation\Struct\TranslationContext;
use Psr\Container\ContainerInterface;

interface TranslationApiInterface {
	/**
	 * Set the translation context
	 * 
	 * @param  TranslationContext $context
	 */
    public function setContext(TranslationContext $context): void;

    /**
     * @return TranslationContext
     */
    public function getContext(): TranslationContext;

	/**
	 * Translates the given string
	 * @param  string $input
	 * @return string
	 */
	public function translateString(string $input): string;

	/**
	 * Returns a readable name for the API
	 * @return string
	 */
	public function getLabel(): string;
	
	/**
	 * Returns a unique identifier for this API.
	 * @return string
	 */
	public function getIdentifier(): string;
	
	/**
	 * @return boolean
	 */
	public function isAvailable(): bool;

	/**
	 * @return array
	 */
	public function getSupportedLanguages(): array;

	/**
	 * Checks if a given lanuage is supported by the API
	 * 
	 * @param  string $language The Language as a ISO-639-1 code
	 * @return boolean
	 */
	public function isLanguageSupported(string $language): bool;
}