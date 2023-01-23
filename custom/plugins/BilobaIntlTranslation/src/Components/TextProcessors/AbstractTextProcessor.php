<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\TextProcessors;

use Biloba\IntlTranslation\Struct\TranslationContext;
use Biloba\IntlTranslation\Components\TextProcessorInterface;

abstract class AbstractTextProcessor implements TextProcessorInterface {

	/**
	 * Method run on the text before it's translated.
	 * 
	 * @param  string  $text
	 * @return string
	 */
	public function preTranslate(TranslationContext $context, string $text): string
	{
		return $text;
	}

	/**
	 * Method run on the text before it's translated.
	 * 
	 * @param  string  $text
	 * @return string
	 */
	public function postTranslate(TranslationContext $context, string $text): string
	{
		return $text;
	}

	/**
	 * The return value of this method determins in which order
	 * the processor are executed.
	 * 
	 * @return int
	 */
	public function getPriority(): int
	{
		return 1;
	}

	/**
	 * Helper function that retruns true if the given text contains HTML tags.
	 * 
	 * @param  string $text
	 * @return boolean
	 * */
	protected function hasTextHtml($text): bool
	{
		return $text != strip_tags($text);
	}
}