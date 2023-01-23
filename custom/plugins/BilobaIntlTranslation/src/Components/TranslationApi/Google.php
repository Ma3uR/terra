<?php declare(strict_types=1);
/**
 * (c) 2018 - Biloba IT Balleyer & Lohrmann GbR
 * All Rights reserved
 *
 * Contact: Biloba IT <kontakt@biloba-it.de>
 */
namespace Biloba\IntlTranslation\Components\TranslationApi;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class Google extends AbstractTranslationApi {
	/**
	 * @var string
	 */
	private $sourceLanguage;

	private $supportedLanguages;

	public function __construct(LoggerInterface $log) {
		$this->log = $log;
	}

	/**
	 * translates the $input to the target language $to and sends the response back
	 *
     * @param string $input The input to translate.
     * @return string
	 */
	public function translateString(string $input): string
	{	
		// the google api allows locales for specific languages
		// check the locales first, and then the languages
		$sourceLanguage = $this->getContext()->getSourceLanguage()->getLocale()->getCode();
		if(!$this->isLanguageSupported($sourceLanguage)){
			$sourceLanguage = $this->getContext()->getSourceLanguageShort();
		}

		$targetLanguage = $this->getContext()->getTargetLanguage()->getLocale()->getCode();
		if(!$this->isLanguageSupported($targetLanguage)){
			$targetLanguage = $this->getContext()->getTargetLanguageShort();
		}

		// setup the request
		$requestBody = [
			'q' => $input,
			'source' => $sourceLanguage,
			'target' => $targetLanguage
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

			$this->log->debug("Detected HTML", $input);

			$requestBody['format'] = 'html';
		}


		// log for debugging only
		$this->log->debug("Translation via Google", $requestBody);

		$translateResult = $this->request('/', $requestBody);

		// log for debugging only
		$this->log->debug("Google Result", $translateResult);
		
		//check if excpeted data given
		if(isset($translateResult['translations']) && count($translateResult['translations']) > 0){

			//return first found translation
			return $translateResult['translations'][0]['translatedText'];
		}
	}

	/**
	 * translates the $input to the target language $to and sends the response back
	 *
     * @param string $input The input to translate.
     * @return mixed
	 */
	private function request($path, $requestBody=null)
	{
		$path = 'https://translation.googleapis.com/language/translate/v2' . $path;

		if($requestBody == null) {
			$requestBody = [];
		}

		$requestBody['key'] = $this->getApiKey();

		$requestTimeout = $this->getContext()->getPluginConfigOption('ApiTimeout', 60);

		try {
			//creates new Client for Google API interaction
			$client = new Client([
			    // You can set any number of default request options.
			    'timeout'  => $requestTimeout,
			]);

			//sends input per post to the Google API and returns the response
			$response = $client->post($path, [
		    	'form_params' => $requestBody
			]);

			//check if request succeeded
			if($response->getStatusCode() == 200) {

				//parse response json into assoc array
				$responseBody = json_decode("".$response->getBody(), true);

				if($responseBody['data']) {
					return $responseBody['data'];
				}

			} else {
				// log a error and throw a exception
				$this->log->error("Error with Google API! Status Code " . $response->getStatusCode(), ['request' => $requestBody, 'response' => "".$response->getBody()]);
				throw new Exceptions\UnknownApiErrorException('Google');	
			}
		//Guzzle expection handling
		} catch(\GuzzleHttp\Exception\ConnectException $exception) {
			throw new Exceptions\RequestTimeoutException('Google');
		} catch (\GuzzleHttp\Exception\RequestException $exception) {

			//get response object
			$response = $exception->getResponse();

			// log the error to the debug log
			$this->log->error("Error with Google API! Status Code " . $response->getStatusCode(), ['request' => $requestBody, 'body' => "".$response->getBody()]);

		    //return correct error code
		    //if the google API key is invalid
			if($response->getStatusCode() == 403) {
				throw new Exceptions\InvalidApiKeyException('Google');

			//if the character limit has been reached
	    	} elseif($response->getStatusCode() == 429) {
				throw new Exceptions\RequestLimitException('Google');

			//if everything else
			} else {
				throw new Exceptions\UnknownApiErrorException('Google');
			}
		}		
	}

	public function getIdentifier(): string
	{
		return 'google';
	}

	public function getLabel(): string
	{
    	return 'Google Translate';
	}
	
	public function isAvailable(): bool
	{
		return $this->getApiKey() != '';
	}

	private function getApiKey(): string
	{
		return $this->getContext()->getPluginConfigOption('GoogleApiKey', '');
	}

	public function getSupportedLanguages(): array
	{
		if(!$this->supportedLanguages) {
			$apiResponse = $this->request('/languages');
			$this->supportedLanguages = [];

			foreach($apiResponse['languages'] as $lang) {
				$this->supportedLanguages[] = strtolower($lang['language']);
			}
		}

		return $this->supportedLanguages;
	}
	
	public function isValid(): bool
	{
		$this->getSupportedLanguages();
		return true;
	}
}