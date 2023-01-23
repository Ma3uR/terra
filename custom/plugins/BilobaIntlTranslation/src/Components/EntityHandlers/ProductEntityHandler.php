<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\EntityHandlers;

class ProductEntityHandler extends GenericEntityHandler {
	protected $entityFields = [
		'product' => ['metaDescription', 'name', 'keywords', 'description', 'metaTitle', 'packUnit']
	];
}