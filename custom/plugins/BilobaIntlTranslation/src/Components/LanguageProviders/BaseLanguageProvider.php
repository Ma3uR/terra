<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\LanguageProviders;

use Biloba\IntlTranslation\Components\LanguageProviderInterface;

class BaseLanguageProvider implements LanguageProviderInterface
{
	public function getSupportedLanguages(): array 
	{
		return ['de','en'];
	}
}