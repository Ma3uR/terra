<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Service\Exceptions;

use Biloba\IntlTranslation\Components\BilobaIntlTranslationException;

class ExceededTestTranslationsException extends BilobaIntlTranslationException {
	public const CODE = 10;

	public function __construct()
	{
		parent::__construct(self::CODE, "The amount of test translations was exceeded.");
	}
}