<?php

/**
 * Tests for the CCAI client
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Tests\CCAI;

use CloudContactAI\CCAI\CCAI;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CCAITest extends TestCase
{
    /**
     * Test client initialization with production URLs (default)
     */
    public function testInitialization(): void
    {
        $clientId = 'test-client-id';
        $apiKey = 'test-api-key';
        $ccai = new CCAI([
            'clientId' => $clientId,
            'apiKey'   => $apiKey,
        ]);

        $this->assertEquals($clientId, $ccai->getClientId());
        $this->assertEquals($apiKey, $ccai->getApiKey());
        $this->assertFalse($ccai->isTestEnvironment());
        $this->assertEquals('https://core.cloudcontactai.com/api', $ccai->getBaseUrl());
        $this->assertEquals('https://email-campaigns.cloudcontactai.com/api/v1', $ccai->getEmailBaseUrl());
        $this->assertEquals('https://files.cloudcontactai.com', $ccai->getFilesBaseUrl());
    }

    /**
     * Test client initialization with test environment URLs
     */
    public function testInitializationTestEnvironment(): void
    {
        $ccai = new CCAI([
            'clientId'           => 'test-client-id',
            'apiKey'             => 'test-api-key',
            'useTestEnvironment' => true,
        ]);

        $this->assertTrue($ccai->isTestEnvironment());
        $this->assertEquals('https://core-test-cloudcontactai.allcode.com/api', $ccai->getBaseUrl());
        $this->assertEquals('https://email-campaigns-test-cloudcontactai.allcode.com/api/v1', $ccai->getEmailBaseUrl());
        $this->assertEquals('https://files-test-cloudcontactai.allcode.com', $ccai->getFilesBaseUrl());
    }

    /**
     * Test client initialization with custom URL overrides
     */
    public function testInitializationCustomUrls(): void
    {
        $ccai = new CCAI([
            'clientId'    => 'test-client-id',
            'apiKey'      => 'test-api-key',
            'baseUrl'     => 'https://custom.api.example.com',
            'emailBaseUrl'=> 'https://custom.email.example.com',
            'filesBaseUrl'=> 'https://custom.files.example.com',
        ]);

        $this->assertEquals('https://custom.api.example.com', $ccai->getBaseUrl());
        $this->assertEquals('https://custom.email.example.com', $ccai->getEmailBaseUrl());
        $this->assertEquals('https://custom.files.example.com', $ccai->getFilesBaseUrl());
    }

    /**
     * Test validation during initialization
     */
    public function testInitializationValidation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Client ID is required');
        new CCAI([
            'apiKey' => 'test-api-key'
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API Key is required');
        new CCAI([
            'clientId' => 'test-client-id'
        ]);
    }

    /**
     * Test the request method
     */
    public function testRequest(): void
    {
        // Create a mock response
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'success']))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $ccai = new CCAI([
            'clientId' => 'test-client-id',
            'apiKey' => 'test-api-key'
        ], $client);

        // Test GET request
        $result = $ccai->request('GET', '/test-endpoint');
        $this->assertEquals(['status' => 'success'], $result);
    }

    /**
     * Test error handling in the request method
     */
    public function testRequestError(): void
    {
        // Create a mock response with error
        $mock = new MockHandler([
            new Response(400, [], json_encode(['error' => 'Bad request']))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $ccai = new CCAI([
            'clientId' => 'test-client-id',
            'apiKey' => 'test-api-key'
        ], $client);

        // Test error handling
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API Error: 400');
        $ccai->request('GET', '/test-endpoint');
    }
}
