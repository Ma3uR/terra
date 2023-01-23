<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\DhlBcpConfigScraper;

use Exception;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Communicates with DHL Business Customer Portal (BCP) to check login credentials and scrape configuration for
 * webservice.
 */
class DhlBcpConfigScraper
{
    private const SESSION_COOKIE_NAME = 'JSESSIONID';
    private const DHL_BCP_URL = 'https://www.dhl-geschaeftskundenportal.de/webcenter/portal/gkpExternal';
    private const HTTP_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36';
    private const ERROR_MSG_SYSTEMUSER_EN = 'You are trying to log in with a user that may only be used for web services or partner applications.';
    private const ERROR_MSG_SYSTEMUSER_DE = 'Du versuchst dich mit einem Benutzer anzumelden, der nur f&uuml;r die Verwendung f&uuml;r Webservices oder Partneranwendungen genutzt werden kann.';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $username
     * @param string $password
     * @throws DhlBcpConfigScraperInvalidCredentialsException
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;

        if ($this->username === '' || $this->password === '') {
            throw DhlBcpConfigScraperInvalidCredentialsException::usernameOrPasswordMissing();
        }

        $this->client = new Client();
        $this->client->setServerParameter('HTTP_USER_AGENT', self::HTTP_USER_AGENT);
    }

    /**
     * Try to access the "DHL Business Customer Portal" using the provided credentials.
     *
     * @throws AbstractDhlBcpConfigScraperException
     */
    public function checkCredentials(): CheckCredentialsResult
    {
        try {
            $this->performAdfAfrLoop();
            $this->login();

            return CheckCredentialsResult::credentialsAreValid();
        } catch (DhlBcpConfigScraperInvalidCredentialsException $e) {
            return CheckCredentialsResult::credentialsAreInvalid();
        } catch (DhlBcpConfigScraperUserIsSystemUserException $e) {
            return CheckCredentialsResult::userIsSystemUser();
        } catch (AbstractDhlBcpConfigScraperException $e) {
            throw $e;
        } catch (Exception $e) {
            // Wrap any error, because that probably means the website changed and made this component incompatible
            throw DhlBcpConfigScraperCommunicationException::unexpectedError($e);
        }
    }

    /**
     * Access the "DHL Business Customer Portal" using the provided credentials, then read the customer number and the
     * billing numbers from the admin center.
     *
     * @throws AbstractDhlBcpConfigScraperException
     */
    public function fetchContractData(): DhlContractData
    {
        try {
            $this->performAdfAfrLoop();
            $this->login();

            return $this->getContractData();
        } catch (AbstractDhlBcpConfigScraperException $e) {
            throw $e;
        } catch (Exception $e) {
            // Wrap any error, because that probably means the website changed and made this component incompatible
            throw DhlBcpConfigScraperCommunicationException::unexpectedError($e);
        }
    }

    /**
     * Build an initial browser session with the Oracle ADF on the other side.
     * Oracle ADF Faces is such advanced enterprise technology that it needs to detect whether the user has opened a new
     * browser window or tab, because that might otherwise cause it to malfunction. Fine and dandy.
     */
    private function performAdfAfrLoop(): void
    {
        $this->client->followRedirects(false); // BrowserKit's redirect support doesn't handle the arf loop correctly.

        // Step 1. Make a request to the home page to get a Java session cookie and the afrLoop token, which we arduously
        // have to extract from some JavaScript (the "loopback script").
        $this->crawler = $this->client->request('GET', static::DHL_BCP_URL);
        $afrLoopToken = $this->extractAfrLoopToken();
        $sessionToken = $this->client->getCookieJar()->get(static::SESSION_COOKIE_NAME, '/webcenter')->getValue();

        // Step 2. Call the home page again, this time passing the session cookie as part of the url.
        // Also pass the afr loop token as part of the query string. This step looks redundant, but is required
        // to initialize the session. This is fun, right?
        $this->client->request('GET', static::DHL_BCP_URL . ";jsessionid=${sessionToken}?_afrLoop=${afrLoopToken}");

        // Step 3. Now we have to request the home page again, this time only passing the afr loop token. This returns
        // the loopback script again, this time with a new afr loop token.
        $this->crawler = $this->client->request('GET', static::DHL_BCP_URL . "?_afrLoop=${afrLoopToken}");
        $afrLoopToken = $this->extractAfrLoopToken();

        // Step 4. Now we can request the home page again with the new afr loop token. We have finally managed to load
        // the actual home page. Yay!
        $this->crawler = $this->client->request('GET', static::DHL_BCP_URL . "?_afrLoop=${afrLoopToken}");

        $this->client->followRedirects(true);
    }

    /**
     * Extract the "afr loop" token from Oracle ADF's "loopback script".
     *
     * @return string the afr loop token
     * @throws DhlBcpConfigScraperCommunicationException
     */
    private function extractAfrLoopToken(): string
    {
        $loopbackPage = $this->crawler->html();
        $matches = [];
        $matchResult = preg_match(
            '/query = _addParam\\(query, "_afrLoop", "([^"]+)"\\);/',
            $loopbackPage,
            $matches
        );
        if (!$matchResult) {
            throw DhlBcpConfigScraperCommunicationException::loopTokenNotFound();
        }

        return $matches[1];
    }

    /**
     * Login using the login form.
     */
    private function login(): void
    {
        // Step 5. Fill in the login form and POST it to log in, adding some magic POST values and the Adf-Rich-Message
        // header so ADF thinks we're its client.
        $loginForm = $this->getADFMainForm();
        $process = null;
        foreach ($loginForm->getValues() as $name => $value) {
            if (mb_strpos($name, 'username')) {
                $loginForm->setValues([$name => $this->username]);
                $process = mb_substr($name, 0, mb_strpos($name, ':pt:'));
            }
            if (mb_strpos($name, 'password')) {
                $loginForm->setValues([$name => $this->password]);
            }
        }

        $event = "${process}:pt:loginBb:cbLogin";
        $this->crawler = $this->submitForm(
            $loginForm,
            [
                'oracle.adf.view.rich.RENDER' => $process,
                'oracle.adf.view.rich.PROCESS' => $process,
                'event' => $event,
                'event.' . $event => '<m xmlns="http://oracle.com/richClient/comm"><k v="type"><s>action</s></k></m>',
            ],
            [
                'Adf-Rich-Message' => 'true',
            ]
        );

        $this->checkWhetherLoginFailed();

        // Step 6. Login was apparently successful, so we can now load the actual portal using the URL from the
        // login XHR's response.
        $portalUri = html_entity_decode($this->crawler->filterXPath('//redirect')->text());
        $this->crawler = $this->client->request('GET', 'https://www.dhl-geschaeftskundenportal.de' . $portalUri);
    }

    /**
     * @return Form the form named "f1" from the current page.
     */
    private function getADFMainForm(): Form
    {
        if ($this->crawler->filter('form')->count() === 0) {
            // Start another browser session if the form is empty.
            // Sometimes it happens that the session closes it self after the login (no idea why)
            // so the crawler can't get the data in that case.
            $this->performAdfAfrLoop();
        }

        return $this->crawler->filter('form[name=f1]')->form();
    }

    /**
     * Submit a form.
     *
     * This is different from Symfony\Component\BrowserKit\Client::submit($form) in that it can add additional values to
     * the form without us having to create DOM elements for them first.
     *
     * @param Form $form the form to submit
     * @param array $extraValues any extra values to add to the form
     * @param array $headers any extra headers to pass to the server
     * @return Crawler the resulting page
     */
    private function submitForm(Form $form, array $extraValues = [], array $headers = []): Crawler
    {
        $formValues = array_merge($form->getValues(), $extraValues);

        // We pass the form values as $body and not as $parameters to $this->client->request() because parameter values starting
        // with an @ are not correctly handled by Guzzle. If using an @ the value will be interpreted as file name and the
        // file will be uploaded instead.
        $body = http_build_query($formValues);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';

        // The method \Symfony\Component\BrowserKit\Client::request expects the headers to be passed as a key-value
        // with the headers name in the format HTTP_HEADER_NAME. So header names need to be converted here.
        $server = array_combine(array_map(function ($headerName) {
            return 'HTTP_' . mb_strtoupper(str_replace('-', '_', $headerName));
        }, array_keys($headers)), $headers);

        return $this->client->request($form->getMethod(), $form->getUri(), [], [], $server, $body);
    }

    /**
     * Checks whether the login has failed. We can tell by inspecting the response of the login request.
     * If the login failed, the XHR's response contains the updated login form inside an XML <fragment> element.
     *
     * @throws DhlBcpConfigScraperInvalidCredentialsException
     */
    private function checkWhetherLoginFailed(): void
    {
        $contentFragment = $this->crawler->filterXPath('//fragment');
        if ($contentFragment->count()) {
            // We are looking for this error message:
            // You are trying to log in with a user that may only be used for web services or partner applications. Login to the desktop of the DHL Business Customer Portal is not possible with this user.
            // Du versuchst dich mit einem Benutzer anzumelden, der nur für die Verwendung für Webservices oder Partneranwendungen genutzt werden kann. Eine Anmeldung mit diesem Benutzer am Geschäftskundenportal ist nicht möglich.
            if (str_contains($contentFragment->text(), self::ERROR_MSG_SYSTEMUSER_DE)
                || str_contains($contentFragment->text(), self::ERROR_MSG_SYSTEMUSER_EN)
            ) {
                throw new DhlBcpConfigScraperUserIsSystemUserException();
            }

            throw DhlBcpConfigScraperInvalidCredentialsException::loginFailed();
        }
    }

    /**
     * Navigate from the "Business Customer Portal" dashboard to the Contract data portal and
     * read the customer number.
     */
    private function getContractData(): DhlContractData
    {
        // Step 7. Get the contract data address page (AJAX fragment) and extract the customerNumber.
        $this->crawler = $this->getContractDataFromAddressPage();
        $customerNumber = $this->extractCustomerNumberFromCrawler($this->crawler);

        // Step 8. Now fetch the "Vertragspositionen" tab (AJAX fragment).
        $this->crawler = $this->getContractPositions();

        return new DhlContractData(
            $customerNumber,
            $this->extractBookedProductsFromCrawler($this->crawler)
        );
    }

    /**
     * Gets the contract data address page (AJAX fragment).
     *
     * @return Crawler
     */
    private function getContractDataFromAddressPage(): Crawler
    {
        return $this->extractContractData(
            $this->getADFMainForm(),
            [
                'event' => 'T:sf_adminj_id_2:cmdc_admin',
                'event.T:sf_adminj_id_2:cmdc_admin' => '<m xmlns="http://oracle.com/richClient/comm"><k v="type"><s>action</s></k></m>',
                'oracle.adf.view.rich.PPR_FORCED' => 'true',
            ]
        );
    }

    /**
     * Fetches the "Vertragspositionen" tab data (AJAX fragment).
     *
     * @return Crawler
     */
    private function getContractPositions(): Crawler
    {
        return $this->extractContractData(
            $this->getADFMainForm(),
            [
                'event' => 'T:oc_519316203region1:pt:np:1:cni',
                'event.T:oc_519316203region1:pt:np:1:cni' => '<m xmlns="http://oracle.com/richClient/comm"><k v="type"><s>action</s></k></m>',
                'oracle.adf.view.rich.PROCESS' => 'T:oc_519316203region1',
            ]
        );
    }

    /**
     * @param Form $navigationForm
     * @param array $submitEventType
     * @return Crawler
     */
    private function extractContractData(Form $navigationForm, array $submitEventType): Crawler
    {
        $contractData = $this->submitForm(
            $navigationForm,
            $submitEventType,
            [
                'Accept' => '*/*',
                'Adf-Rich-Message' => 'true',
            ]
        );

        // This step is necessary because the contract data page is transmitted as HTML-as-CDATA inside an XML message,
        // so we feed the inner CDATA section to the crawler.
        $contractAddressFragment = $contractData->filterXPath('//fragment')->text();

        return new Crawler($contractAddressFragment, $contractData->getUri());
    }

    /**
     * @param Crawler $crawler
     * @return string
     */
    private function extractCustomerNumberFromCrawler(Crawler $crawler): string
    {
        return $crawler->filter('.af_panelGroupLayout')
            ->reduce(function (Crawler $div) {
                return mb_strpos($div->attr('id'), 'pt:pglCE') !== false;
            })
            ->filter('.text')
            ->text();
    }

    /**
     * @param Crawler $crawler
     * @return array Associative array with values as array's.
     */
    private function extractBookedProductsFromCrawler(Crawler $crawler): array
    {
        $billingNumberSets = [];
        $crawler->filter('.af_panelGroupLayout')
            ->reduce(function (Crawler $div) {
                return mb_strpos($div->attr('id'), 'rANpN') !== false;
            })
            ->each(function (Crawler $cell) use (&$billingNumberSets) {
                $productName = $cell->children()->getNode(1)->textContent;
                $billingNumber = $cell->children()->getNode(0)->textContent;

                if (!isset($billingNumberSets[$productName])) {
                    $billingNumberSets[$productName] = [];
                }
                $billingNumberSets[$productName][] = $billingNumber;
            });

        return DhlContractDataBookedProduct::createFromBcpProductNameBillingNumbersMapping($billingNumberSets);
    }
}
