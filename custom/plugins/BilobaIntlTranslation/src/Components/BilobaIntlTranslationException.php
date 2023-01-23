<?php declare(strict_types=1);
/**
 * (c) 2019 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components;

class BilobaIntlTranslationException extends \Exception {
	public $errorCode = null;

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @param int $code
     *
     * @return self
     */
    public function setErrorCode(int $errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function __construct($errorCode, $message) {
    	parent::__construct($message);
    	
    	$this->errorCode = $errorCode;
    }
}