<?php declare(strict_types=1);
/**
 * (c) 2019 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace  Biloba\IntlTranslation\Components\TranslationApi\Exceptions;

use Biloba\IntlTranslation\Components\BilobaIntlTranslationException;

class RateLimitException extends BilobaIntlTranslationException {
	public const CODE = 2;

	public function __construct($api) {
		parent::__construct(self::CODE, "'$api' limits requests!");
	}
}