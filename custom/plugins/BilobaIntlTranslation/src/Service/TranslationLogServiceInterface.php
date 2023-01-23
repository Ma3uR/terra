<?php declare(strict_types=1);

/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Service;

interface TranslationLogServiceInterface {
	const TYPE_TRANSLATE = 'translate';
	const TYPE_RETRANSLATE = 'retranslate';
	const MAX_TRANSLATIONS = 10;
}