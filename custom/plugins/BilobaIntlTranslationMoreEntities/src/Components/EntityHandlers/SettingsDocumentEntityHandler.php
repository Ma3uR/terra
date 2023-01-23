<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslationMoreEntities\Components\EntityHandlers;

use Biloba\IntlTranslation\Components\EntityHandlers\GenericEntityHandler;

class SettingsDocumentEntityHandler extends GenericEntityHandler {
	protected $entityFields = [
		'document' => ['name', 'description', 'customFields']
	];
}