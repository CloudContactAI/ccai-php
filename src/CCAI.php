<?php

/**
 * CCAI.php - A PHP module for interacting with the Cloud Contact AI API
 * This module provides functionality to send SMS messages through the CCAI platform.
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI;

use CloudContactAI\CCAI\SMS\SMS;
use CloudContactAI\CCAI\SMS\MMS;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
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
     * @var string Base URL for the API
     */
    public string $baseUrl;
    
    /**
     * @param string $clientId Client ID for authentication
     * @param string $apiKey API key for authentication
     * @param string $baseUrl Base URL for the API
     */
    public function __construct(
        string $clientId,
        string $apiKey,
        string $baseUrl = 'https://core.cloudcontactai.com/api'
    ) {
        $this->clientId = $clientId;
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
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
     * Create a new CCAI client instance
     *
     * @param array $config Configuration array containing clientId and apiKey
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
            $config['baseUrl'] ?? 'https://core.cloudcontactai.com/api'
        );

        $this->httpClient = $httpClient ?? new Client();
        $this->sms = new SMS($this);
        $this->mms = new MMS($this);
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
     * Get the base URL
     *
     * @return string Base URL
     */
    public function getBaseUrl(): string
    {
        return $this->config->baseUrl;
    }

    /**
     * Make an authenticated API request to the CCAI API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request data
     * @param int $timeout Request timeout in seconds
     * 
     * @return array API response
     * 
     * @throws RuntimeException If the API returns an error
     */
    public function request(string $method, string $endpoint, ?array $data = null, int $timeout = 30): array
    {
        $url = $this->config->baseUrl . $endpoint;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->config->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => '*/*'
        ];

        $options = [
            'headers' => $headers,
            'timeout' => $timeout
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
