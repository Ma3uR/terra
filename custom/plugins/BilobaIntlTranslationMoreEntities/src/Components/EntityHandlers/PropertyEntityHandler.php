<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslationMoreEntities\Components\EntityHandlers;

use Biloba\IntlTranslation\Components\EntityHandlers\GenericEntityHandler;

class PropertyEntityHandler extends GenericEntityHandler {
	protected $entityFields = [
		'property_group' => ['name', 'description']
	];
}