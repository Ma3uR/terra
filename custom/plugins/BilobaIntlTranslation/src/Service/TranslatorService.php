<?php declare(strict_types=1);

/**
 * (c) 2020 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Service;

use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Biloba\IntlTranslation\Struct\TranslationContext;
use Biloba\IntlTranslation\Struct\TranslatableFieldCollection;
use Biloba\IntlTranslation\Components\TranslationApiInterface;
use Biloba\IntlTranslation\Components\EntityHandlerInterface;
use Biloba\IntlTranslation\Components\LanguageProviderInterface;
use Biloba\IntlTranslation\Components\TextProcessorInterface;
use Psr\Log\LoggerInterface;
use Biloba\IntlTranslation\Service\TranslationLogServiceInterface;
use Biloba\IntlTranslation\Service\LicenceServiceInterface;
use Biloba\IntlTranslation\Service\Exceptions\ExceededTestTranslationsException;

class TranslatorService implements TranslatorServiceInterface {
	/**
	 * @var RewindableGenerator
	 */
	private $apis = null;
	
	/**
	 * @var RewindableGenerator
	 */
	private $entityHandlers = null;

	/**
	 * @var RewindableGenerator
	 */
	private $languageProviders = null;

	/**
	 * @var RewindableGenerator
	 */
	private $textProcessors = null;

	/**
	 * @var TranslationContext
	 */
	private $context;

	/**
	 * @var LoggerInterface
	 */
	private $log;

	/**
	 * @var TranslationLogServiceInterface
	 */
	private $translationLogService;

	/**
	 * @var LicenceServiceInterface
	 */
	private $licenceService;

	/**
	 * @var string[]
	 */
	private $supportedLanguages;

	/**
     * @throws \InvalidArgumentException
     **/
	public function __construct(RewindableGenerator $apis, 
								RewindableGenerator $entityHandlers, 
								RewindableGenerator $languageProviders, 
								RewindableGenerator $textProcessors, 
								LoggerInterface $log,
								TranslationLogServiceInterface $translationLogService,
								LicenceServiceInterface $licenceService) {
		$this->apis = $apis;
		foreach($this->apis as $api) {
			// check if the register handler is supported
			if(!($api instanceof TranslationApiInterface)) {
				throw new \InvalidArgumentException(get_class($api) . " is not a valid api. Must implement " . TranslationApiInterface::class);
			}
		}
		
		$this->entityHandlers = $entityHandlers;
		foreach($this->entityHandlers as $entityHandler) {
			// check if the register handler is supported
			if(!($entityHandler instanceof EntityHandlerInterface)) {
				throw new \InvalidArgumentException(get_class($entityHandler) . " is not a valid entity handler. Must implement " . EntityHandlerInterface::class);
			}
		}

		$this->languageProviders = $languageProviders;
		foreach($this->languageProviders as $languageProvider) {
			// check if the register handler is supported
			if(!($languageProvider instanceof LanguageProviderInterface)) {
				throw new \InvalidArgumentException(get_class($languageProvider) . " is not a valid language provider. Must implement " . LanguageProviderInterface::class);
			}
		}

		$this->textProcessors = $textProcessors;
		foreach($this->textProcessors as $textProcessor) {
			// check if the register handler is supported
			if(!($textProcessor instanceof TextProcessorInterface)) {
				throw new \InvalidArgumentException(get_class($textProcessor) . " is not a valid language provider. Must implement " . TextProcessorInterface::class);
			}
		}

		$this->log = $log;
		$this->translationLogService = $translationLogService;
		$this->licenceService = $licenceService;
	}

	/**
	 * Translates the entitiy identified by $type with the ID given in $id
	 * 
	 * @param  string $type The entitiy type
	 * @param  string $id   The entity id
	 * @return array
	 *
	 * @throws Biloba\IntlTranslation\Service\Exceptions\ExceededTestTranslationsException
	 */
	public function translate(string $type, string $id): array
	{
		$initiator = $this->getContext()->getInitiator();
		$shopwareContext = $this->getContext()->getShopwareContext();

		// check if we have test translations left
		$hasLicence = $this->licenceService->hasValidLicence($initiator, $shopwareContext);
		if(!$hasLicence) {
			$numberOfTranslations = $this->translationLogService->getNumberOfTranslations($this->context);
			if($numberOfTranslations >= TranslationLogServiceInterface::MAX_TRANSLATIONS){
				throw new ExceededTestTranslationsException();
			}
		}

        $translatedFields = [];

        // collect all translatable fields
        $translatableFields = $this->collectTranslatableFields($type);

        // collect all source values for the translatable fields
        $sourceFields = $this->collectSourceValues($type, $id, $translatableFields);
        foreach($sourceFields as $field) {

            $targetValue = $this->translateString($field->getValue());
            $translatedFields[$field->getName()] = $targetValue;
        }

        // check if we have to write a translation
        if(count(array_values($translatedFields)) > 0) {
	        // update translation in datbase
	        $this->updateTranslations($type, $id, $translatedFields);

	        // update the translation log in the database
	        $this->translationLogService->write($this->context, TranslationLogServiceInterface::TYPE_TRANSLATE, 'success');
	    }

        return $translatedFields;
	}

	/**
	 * Translates a string. 
	 * 
	 * @param  string $source
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function translateString(string $source): string
	{
		$this->log->debug("Translate " . $source);

		$translationApi = $this->getCurrentTranslationApi();
		if(!$translationApi) {
			throw new \Exception("Invalid translation API!", 1);
		}

		// set the translation context
		$translationApi->setContext($this->context);

		// run text pre processors
		$source = $this->runPreProcessors($source);

		// translate the string via the given api
		$source = $translationApi->translateString($source);

		// run text post processors
		$source = $this->runPostProcessors($source);

		return $source;
	}

	/**
	 * Returns the translation API that is defined in the current context.
	 *
	 * @return TranslationApiInterface|null
	 */
	private function getCurrentTranslationApi(): ?TranslationApiInterface {

		if($this->context) {
			return $this->getTranslationApiByIdentifier($this->context->getApi());
		}

		return null;
	}

	/**
	 * Returns a translation API by its identifier
	 *
	 * @param  string $identifier
	 * @return TranslationApiInterface|null
	 */
	public function getTranslationApiByIdentifier(string $identifier): ?TranslationApiInterface
	{
		foreach($this->apis as $api) {
			$api->setContext($this->getContext());
			
			if($api->getIdentifier() == $identifier) {
				return $api;
			}
		}

		return null;
	}

	/**
	 * Returns all translation API
	 *
	 * @return iterator
	 */
	public function getTranslationApis() {
		$ret = [];

		foreach($this->apis as $api) {
			$api->setContext($this->getContext());
			
			if($api->isAvailable()) {
				$ret[] = $api;
			}
		}

		return $ret;
	}

	/**
	 * Returns all entity handlers
	 *
	 * @return iterator
	 */
	public function getEntityHandlers() {
		return $this->entityHandlers;
	}

	/**
	 * Filters all entity handlers by the given name
	 * 
	 * @param string $name
	 * @return iterator
	 */
	public function getHandlersForEntity($name) {
		$ret = [];
		
		foreach($this->entityHandlers as $handler){

			// check if the handler supports the given entity
			if($handler->isSupported($name)) {
				$ret[] = $handler;
			}
		}

		return $ret;
	}

	// @todo documentation
	public function collectTranslatableFields($type): TranslatableFieldCollection
	{
		$collection = new TranslatableFieldCollection();

        // Fire Evente to collect entity data
        $entityHandlers = $this->getHandlersForEntity($type);

        foreach($entityHandlers as $handler) {

        	$fields = $handler->getFields($type);
        	$collection->append($fields);
        }

        return $collection;
	}

	// @todo documentation
	public function collectSourceValues($type, $id, $translatableFields): TranslatableFieldCollection
	{
		$collection = new TranslatableFieldCollection();

        // Fire Evente to collect entity data
        $entityHandlers = $this->getHandlersForEntity($type);

        foreach($entityHandlers as $handler) {

        	$fields = $handler->getValues($type, $id, $translatableFields);
        	$collection->append($fields);
        }

        return $collection;
	}

	// @todo documentation
	public function updateTranslations($type, $id, $translatedFields): void
	{
        // Fire Evente to collect entity data
        $entityHandlers = $this->getHandlersForEntity($type);

        foreach($entityHandlers as $handler) {

        	$ret = $handler->updateTranslations($type, $id, $translatedFields);
        	if(!$ret) {
        		break;
        	}
        }
	}

	/**
	 * Set the current translation context
	 * @param TranslationContext $context
	 */
	public function setContext(TranslationContext $context): void
	{
		$this->context = $context;
	}

	/**
	 * Get the current translation context
	 * @return TranslationContext
	 */
	public function getContext(): TranslationContext
	{
		return $this->context;
	}

	/**
	 * Get all supported languages
	 * @return string[]
	 */
	public function getSupportedLanguages(): array
	{
		if(!$this->supportedLanguages) {
			
			$this->supportedLanguages = [];

			foreach($this->languageProviders as $languageProvider) {
				$this->supportedLanguages = array_merge($this->supportedLanguages, 
														$languageProvider->getSupportedLanguages());
			}	
		}

		return $this->supportedLanguages;
	}

	/**
	 * Check if a language is supported
	 * @param  string  $language
	 * @return boolean
	 */
	public function isLanguageSupported(string $language): bool
	{
		return in_array($language, $this->getSupportedLanguages());
	}

	/**
	 * Get all usable languages for the given api
	 * @return string[]
	 */
	public function getUsableLanguages(string $identifier): array
	{
		$api = $this->getTranslationApiByIdentifier($identifier);
		
		$supportedLanguages = [];

		foreach($api->getSupportedLanguages() as $apiLanguage) {
			if(in_array($apiLanguage, $this->getSupportedLanguages())) {
				$supportedLanguages[] = $apiLanguage;
			}
		}

		return $supportedLanguages;
	}

	/**
	 * Returns all text processors
	 *
	 * @return iterator
	 */
	public function getTextProcessors() {
		$ret = [];

		foreach($this->apis as $api) {
			$api->setContext($this->getContext());
			
			if($api->isAvailable()) {
				$ret[] = $api;
			}
		}

		return $ret;
	}

	/**
	 * Collects all text processors methods for the given mode.
	 * 
	 * @param  boolean $post
	 * @return array
	 */
	private function collectTextProcessorMethods(bool $post=false): array
	{
		$ret = [];

		foreach($this->textProcessors as $processor) {
			$ret[] = ['instance' => $processor, 'method' => ($post ? 'postTranslate' : 'preTranslate'), 'priority' => $processor->getPriority()];
		}

		usort($ret, function($a, $b) {
			if($a['priority'] > $b['priority']) return ($post ? 1 : -1) * -1;
			else if($a['priority'] < $b['priority']) return ($post ? 1 : -1) * 1;
			else return 0;
		});

		return $ret;
	}

	private function runTextProcessor(string $value, bool $post=false): string 
	{
		$processors = $this->collectTextProcessorMethods($post);

		foreach($processors as $index=>$processor) {
			$value = call_user_func([$processor['instance'], $processor['method']], $this->context, $value);
		}

		return $value;
	}

	private function runPreProcessors(string $value): string
	{
		return $this->runTextProcessor($value, false);
	}


	private function runPostProcessors(string $value): string
	{
		return $this->runTextProcessor($value, true);
	}
}