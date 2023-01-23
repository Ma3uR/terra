<?php declare(strict_types=1);

/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Service;

use Shopware\Core\Framework\Context;

interface LicenceServiceInterface {
	
	/**
	 * Used to check if a plugin has valid licence. Valid means non expired non test licence.
	 * 
	 * @param  string  $pluginName The technical plugin name
	 * @param  Context $context
	 * @return boolean
	 */
	public function hasValidLicence(string $pluginName, Context $context): bool;
}