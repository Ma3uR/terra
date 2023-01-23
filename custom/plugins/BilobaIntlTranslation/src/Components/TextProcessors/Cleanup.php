<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\TextProcessors;

use Biloba\IntlTranslation\Struct\TranslationContext;

class Cleanup extends AbstractTextProcessor {
	
	public function getPriority(): int
	{
		return 1000;
	}

	public function postTranslate(TranslationContext $context, string $text): string
	{	
		// only run processor if given value is html
		if(!$this->hasTextHtml($text)){
			return html_entity_decode($text);
		}
		
		return $text;
	}
}