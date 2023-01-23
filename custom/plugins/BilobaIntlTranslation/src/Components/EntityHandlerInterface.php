<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components;

use Biloba\IntlTranslation\Struct\TranslatableFieldCollection;

interface EntityHandlerInterface {

	/**
	 * Method to check if this handler supports a given entity.
	 * 
	 * @param  string  $name
	 * @return bool
	 */
	public function isSupported(string $name): bool;

    /**
     * @return string[]
     */
    public function getSupportedEntites(): array;

	/**
	* Returns all translatable fields for the given entity.
	* 
	* @param  string  $name
	* @return TranslatableFieldCollection
	*/
	public function getFields(string $name): TranslatableFieldCollection;

	/**
	 * Loads all translatable values for the given entity identified by the given id.
	 * This method MUST return a new instance of TranslatableFieldCollection as the given instance my be 
	 * reused.
	 * 
	 * @param  string                      $name
	 * @param  string                      $id
	 * @param  TranslatableFieldCollection $fields
	 * @return TranslatableFieldCollection 
	 */
	public function getValues(string $name, string $id, TranslatableFieldCollection $fields): TranslatableFieldCollection;

	/**
	 * Save the translated values in the database.
	 * 
	 * @param  string $name
	 * @param  string $id
	 * @param  array  $translatedFields
	 * @return bool Return false to prevent later handles to be executed
	 */
	public function updateTranslations(string $name, string $id, array $translatedFields): bool;
}