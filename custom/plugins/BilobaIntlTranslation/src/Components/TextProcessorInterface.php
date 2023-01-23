<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components;

use Biloba\IntlTranslation\Struct\TranslationContext;

interface TextProcessorInterface {

	/**
	 * Method run on the text before it's translated.
	 * 
	 * @param  string  $text
	 * @return string
	 */
	public function preTranslate(TranslationContext $context, string $text): string;

	/**
	 * Method run on the text before it's translated.
	 * 
	 * @param  string  $text
	 * @return string
	 */
	public function postTranslate(TranslationContext $context, string $text): string;

	/**
	 * The return value of this method determins in which order
	 * the processor are executed.
	 * 
	 * @return int
	 */
	public function getPriority(): int;
}