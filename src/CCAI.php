<?php

/**
 * CCAI.php - A PHP module for interacting with the Cloud Contact AI API
 * This module provides functionality to send SMS, MMS, and email messages,
 * manage webhooks, and handle contact preferences through the CCAI platform.
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI;

use CloudContactAI\CCAI\SMS\SMS;
use CloudContactAI\CCAI\SMS\MMS;
use CloudContactAI\CCAI\Email\Email;
use CloudContactAI\CCAI\Webhook\Webhook;
use CloudContactAI\CCAI\Contact\Contact;
use CloudContactAI\CCAI\ContactValidator\ContactValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Configuration for the CCAI client
 */
class CCAIConfig
{
    /**
     * @var string Client ID for authentication
     */
    public string $clientId;

    /**
     * @var string API key for authentication
     */
    public string $apiKey;

    /**
     * @var string Base URL for the core API
     */
    public string $baseUrl;

    /**
     * @var string Base URL for the Email API
     */
    public string $emailBaseUrl;

    /**
     * @var string Base URL for the Files API
     */
    public string $filesBaseUrl;

    /**
     * @var bool Whether the test environment is active
     */
    public bool $useTestEnvironment;

    /**
     * Production URLs
     */
    private const PROD_BASE_URL  = 'https://core.cloudcontactai.com/api';
    private const PROD_EMAIL_URL = 'https://email-campaigns.cloudcontactai.com/api/v1';
    private const PROD_FILES_URL = 'https://files.cloudcontactai.com';

    /**
     * Test environment URLs
     */
    private const TEST_BASE_URL  = 'https://core-test-cloudcontactai.allcode.com/api';
    private const TEST_EMAIL_URL = 'https://email-campaigns-test-cloudcontactai.allcode.com/api/v1';
    private const TEST_FILES_URL = 'https://files-test-cloudcontactai.allcode.com';

    /**
     * @param string      $clientId           Client ID for authentication
     * @param string      $apiKey             API key for authentication
     * @param bool        $useTestEnvironment Whether to use test environment URLs
     * @param string|null $baseUrl            Override base URL for the core API
     * @param string|null $emailBaseUrl       Override base URL for the Email API
     * @param string|null $filesBaseUrl       Override base URL for the Files API
     */
    public function __construct(
        string $clientId,
        string $apiKey,
        bool $useTestEnvironment = false,
        ?string $baseUrl = null,
        ?string $emailBaseUrl = null,
        ?string $filesBaseUrl = null
    ) {
        $this->clientId = $clientId;
        $this->apiKey = $apiKey;
        $this->useTestEnvironment = $useTestEnvironment;

        // Apply URLs: explicit override > env variable > test/prod default
        $this->baseUrl = $baseUrl
            ?? getenv('CCAI_BASE_URL') ?: ($useTestEnvironment ? self::TEST_BASE_URL : self::PROD_BASE_URL);

        $this->emailBaseUrl = $emailBaseUrl
            ?? getenv('CCAI_EMAIL_BASE_URL') ?: ($useTestEnvironment ? self::TEST_EMAIL_URL : self::PROD_EMAIL_URL);

        $this->filesBaseUrl = $filesBaseUrl
            ?? getenv('CCAI_FILES_BASE_URL') ?: ($useTestEnvironment ? self::TEST_FILES_URL : self::PROD_FILES_URL);
    }
}

/**
 * Main client for interacting with the CloudContactAI API
 */
class CCAI
{
    /**
     * @var CCAIConfig Configuration for the client
     */
    private CCAIConfig $config;

    /**
     * @var ClientInterface HTTP client
     */
    private ClientInterface $httpClient;

    /**
     * @var SMS SMS service
     */
    public $sms;

    /**
     * @var MMS MMS service
     */
    public $mms;

    /**
     * @var Email Email service
     */
    public $email;

    /**
     * @var Webhook Webhook service
     */
    public $webhook;

    /**
     * @var Contact Contact service
     */
    public $contact;

    /**
     * @var ContactValidator Contact validator service
     */
    public $contactValidator;

    /**
     * Create a new CCAI client instance
     *
     * @param array            $config     Configuration array:
     *                                     - clientId (required)
     *                                     - apiKey (required)
     *                                     - useTestEnvironment (bool, default false)
     *                                     - baseUrl (optional override)
     *                                     - emailBaseUrl (optional override)
     *                                     - filesBaseUrl (optional override)
     * @param ?ClientInterface $httpClient Optional HTTP client
     *
     * @throws RuntimeException If required configuration is missing
     */
    public function __construct(array $config, ?ClientInterface $httpClient = null)
    {
        if (empty($config['clientId'])) {
            throw new RuntimeException('Client ID is required');
        }

        if (empty($config['apiKey'])) {
            throw new RuntimeException('API Key is required');
        }

        $this->config = new CCAIConfig(
            $config['clientId'],
            $config['apiKey'],
            $config['useTestEnvironment'] ?? false,
            $config['baseUrl'] ?? null,
            $config['emailBaseUrl'] ?? null,
            $config['filesBaseUrl'] ?? null
        );

        $this->httpClient = $httpClient ?? new Client();
        $this->sms = new SMS($this);
        $this->mms = new MMS($this);
        $this->email = new Email($this);
        $this->webhook = new Webhook($this);
        $this->contact = new Contact($this);
        $this->contactValidator = new ContactValidator($this);
    }

    /**
     * Get the client ID
     *
     * @return string Client ID
     */
    public function getClientId(): string
    {
        return $this->config->clientId;
    }

    /**
     * Get the API key
     *
     * @return string API key
     */
    public function getApiKey(): string
    {
        return $this->config->apiKey;
    }

    /**
     * Get the base URL for the core API
     *
     * @return string Base URL
     */
    public function getBaseUrl(): string
    {
        return $this->config->baseUrl;
    }

    /**
     * Get the base URL for the Email API
     *
     * @return string Email base URL
     */
    public function getEmailBaseUrl(): string
    {
        return $this->config->emailBaseUrl;
    }

    /**
     * Get the base URL for the Files API
     *
     * @return string Files base URL
     */
    public function getFilesBaseUrl(): string
    {
        return $this->config->filesBaseUrl;
    }

    /**
     * Whether the test environment is active
     *
     * @return bool
     */
    public function isTestEnvironment(): bool
    {
        return $this->config->useTestEnvironment;
    }

    /**
     * Make an authenticated API request to the core CCAI API
     *
     * @param string     $method   HTTP method
     * @param string     $endpoint API endpoint (relative to baseUrl)
     * @param array|null $data     Request body data
     * @param int        $timeout  Request timeout in seconds
     *
     * @return array API response
     *
     * @throws RuntimeException If the API returns an error
     */
    public function request(string $method, string $endpoint, ?array $data = null, int $timeout = 30): array
    {
        return $this->doRequest($this->config->baseUrl . $endpoint, $method, $data, $timeout);
    }

    /**
     * Make an authenticated API request to the Email API
     *
     * @param string     $method   HTTP method
     * @param string     $endpoint API endpoint (relative to emailBaseUrl)
     * @param array|null $data     Request body data
     * @param int        $timeout  Request timeout in seconds
     *
     * @return array API response
     *
     * @throws RuntimeException If the API returns an error
     */
    public function emailRequest(string $method, string $endpoint, ?array $data = null, int $timeout = 30): array
    {
        return $this->doRequest($this->config->emailBaseUrl . $endpoint, $method, $data, $timeout, [
            'AccountId' => $this->config->clientId,
            'ClientId'  => $this->config->clientId,
        ]);
    }

    /**
     * Internal HTTP request dispatcher
     *
     * @param string     $url          Full URL
     * @param string     $method       HTTP method
     * @param array|null $data         Request body data
     * @param int        $timeout      Request timeout in seconds
     * @param array      $extraHeaders Additional headers to merge
     *
     * @return array API response
     *
     * @throws RuntimeException If the API returns an error
     */
    private function doRequest(string $url, string $method, ?array $data, int $timeout, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->config->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => '*/*',
        ], $extraHeaders);

        $options = [
            'headers' => $headers,
            'timeout' => $timeout,
        ];

        if ($data !== null) {
            $options['json'] = $data;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();

            return json_decode($body, true) ?? [];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();

                try {
                    $errorData = json_decode($body, true);
                    $errorMessage = is_array($errorData) ? json_encode($errorData) : $body;
                } catch (\JsonException $jsonException) {
                    $errorMessage = $body;
                }

                throw new RuntimeException(
                    sprintf('API Error: %d - %s', $response->getStatusCode(), $errorMessage)
                );
            }

            throw new RuntimeException('No response received from API: ' . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new RuntimeException('Request failed: ' . $e->getMessage());
        }
    }
}
