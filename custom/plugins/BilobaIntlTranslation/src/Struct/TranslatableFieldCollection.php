<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Struct;

use Shopware\Core\Framework\Struct\Collection;

class TranslatableFieldCollection extends Collection {

	/**
	 * Adds a new translatable field to the collection
	 * 
	 * @param string      $name  [description]
	 * @param string|null $value [description]
	 */
	public function addNew(string $name, string $value=null): void
	{
		$this->add(new TranslatableField($name, $value));
	}

	/**
	 * Appends a other collection to this one
	 * @param TranslatableFieldCollection $collection
	 */
	public function append(TranslatableFieldCollection $collection): void
	{
		foreach($collection as $item) {
			$this->add($item);
		}
	}

	/** {@inheritdoc} */
    protected function getExpectedClass(): ?string
    {
        return TranslatableField::class;
    }
}