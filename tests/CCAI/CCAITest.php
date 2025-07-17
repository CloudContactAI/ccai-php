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
     * Test client initialization
     */
    public function testInitialization(): void
    {
        $clientId = 'test-client-id';
        $apiKey = 'test-api-key';
        $ccai = new CCAI([
            'clientId' => $clientId,
            'apiKey' => $apiKey
        ]);

        $this->assertEquals($clientId, $ccai->getClientId());
        $this->assertEquals($apiKey, $ccai->getApiKey());
        $this->assertEquals('https://core.cloudcontactai.com/api', $ccai->getBaseUrl());

        // Test custom base URL
        $customUrl = 'https://custom.api.example.com';
        $ccai = new CCAI([
            'clientId' => $clientId,
            'apiKey' => $apiKey,
            'baseUrl' => $customUrl
        ]);
        $this->assertEquals($customUrl, $ccai->getBaseUrl());
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
