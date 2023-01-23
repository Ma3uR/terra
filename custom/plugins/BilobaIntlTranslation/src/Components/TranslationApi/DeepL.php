<?php declare(strict_types=1);
/**
 * (c) 2018 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace  Biloba\IntlTranslation\Components\TranslationApi;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class DeepL extends AbstractTranslationApi {
	/**
	 * @var array
	 */
	private $config;

	public function __construct(LoggerInterface $log) {
		$this->log = $log;
	}

	/**
	 * Translates the $input to the target language $to and sends the response back
	 *
	 * @param string $input The input to translate.
	 * @return string
	 */
	public function translateString(string $input): string
	{
		//$source = $from ? $from : $this->sourceLanguage;
		$sourceLanguage = $this->getContext()->getSourceLanguageShort();
		$targetLanguage = $this->getContext()->getTargetLanguageShort();
		
		// setup the request
		$requestBody = [
			'text' => $input,
			'target_lang' => strtoupper($targetLanguage),
			'source_lang' => strtoupper($sourceLanguage)
		];

		// enable html processing if necessary 
		if($this->isHtml($input)) {
			// log that we found html
			if(is_array($input)) {
				$this->log->debug("Detected HTML", $input);
			}
			else {
				$this->log->debug("Detected HTML", [$input]);
			}

			$requestBody['tag_handling'] = 'xml';
		}

		// log for debugging only
		$this->log->debug("Translation via DeepL", $requestBody);

		$translateResult = $this->request('/translate', $requestBody);

		// log for debugging only
		$this->log->debug("DeepL Result", $translateResult);

		// check if expected data given
		if(isset($translateResult['translations']) && count($translateResult['translations']) > 0) {
			if(isset($translateResult['translations'][0]['text'])){

				//return first found translation
				return $translateResult['translations'][0]['text'];
			}
		}
	}

	public function getIdentifier(): string
	{
		return 'deepl';
	}

	public function getLabel(): string
	{
    	return 'DeepL';
	}
	
	public function isAvailable():bool
	{
		return $this->getApiKey() != '';
	}

	private function getApiKey():string
	{
		return $this->getContext()->getPluginConfigOption('DeeplApiKey', '');
	}

	public function getSupportedLanguages(): array
	{
		return ['de', 'en', 'fr', 'it', 'ja', 'es', 'nl', 'pl', 'pt', 'ru', 'zh'];
	}

	public function isValid(): bool
	{
		$this->request('/usage');
		return true;
	}

	private function request($path, $requestBody=null)
	{
		$requestUrl = 'https://api.deepl.com/v2' . $path;

		if(!$requestBody){
			$requestBody = [];
		}

		$requestBody['auth_key'] = $this->getApiKey();

		$requestTimeout = $this->getContext()->getPluginConfigOption('ApiTimeout', 60);

		try {
			// creates new HTTP Client for DeepL API interaction
			$client = new Client([
				'timeout'  => $requestTimeout,
			]);
			
			// sends input per post to the DeepL API and returns the response
			$response = $client->post($requestUrl, [
				'form_params' => $requestBody
			]);

			// check if request succeeded
			if($response->getStatusCode() == 200) {
				return json_decode("".$response->getBody(), true);
			}else {
				throw new Exceptions\UnknownApiErrorException('DeepL');
			}
		} catch(\GuzzleHttp\Exception\ConnectException $exception) {
			throw new Exceptions\RequestTimeoutException('DeepL');
		} catch (\GuzzleHttp\Exception\RequestException $exception) {
			// get response object
			$response = $exception->getResponse();

			// return correct error code
			// if DeepL key is invalid
			if($response->getStatusCode() == 403) {
				throw new Exceptions\InvalidApiKeyException('DeepL');
			//if the character limit has been reached
	    	} elseif($response->getStatusCode() == 456) {
				throw new Exceptions\RequestLimitException('DeepL');
			} else {
				throw new Exceptions\UnknownApiErrorException('DeepL');	
			}
		}
	}
}