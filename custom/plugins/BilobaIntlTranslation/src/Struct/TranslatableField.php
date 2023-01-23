<?php declare(strict_types=1);
/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Struct;

use Shopware\Core\Framework\Struct\Struct;

class TranslatableField extends Struct {
	/**
	 * The name of the translatable field
	 * @var string
	 */
	private $name;

    /**
     * The value of the translatable field
     * @var string|null
     */
    private $value;

    /**
     * @param string      $name
     * @param string|null $value
     */
    public function __construct(string $name, string $value=null) {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }
}