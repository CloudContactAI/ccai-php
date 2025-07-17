<?php

/**
 * Tests for the MMS service
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Tests\SMS;

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\MMS;
use CloudContactAI\CCAI\SMS\SMSOptions;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class MMSTest extends TestCase
{
    /**
     * @var CCAI Mock CCAI client
     */
    private $ccai;

    /**
     * @var Client Mock HTTP client
     */
    private $httpClient;

    /**
     * @var MMS MMS service
     */
    private $mms;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->ccai = Mockery::mock(CCAI::class);
        $this->ccai->shouldReceive('getClientId')->andReturn('test-client-id');
        $this->ccai->shouldReceive('getApiKey')->andReturn('test-api-key');
        $this->ccai->shouldReceive('getBaseUrl')->andReturn('https://core.cloudcontactai.com/api');

        $this->httpClient = Mockery::mock(Client::class);
        
        // Create a real MMS instance with mocked dependencies
        $this->mms = new MMS($this->ccai);
        
        // Replace the private httpClient property with our mock
        $reflectionClass = new \ReflectionClass($this->mms);
        $reflectionProperty = $reflectionClass->getProperty('httpClient');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->mms, $this->httpClient);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test getting a signed upload URL
     */
    public function testGetSignedUploadUrl(): void
    {
        // Mock response
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'signedS3Url' => 'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
                'fileKey' => 'test-client-id/campaign/test-file.png'
            ])
        );

        // Set up expectations
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://files.cloudcontactai.com/upload/url',
                [
                    'headers' => [
                        'Authorization' => 'Bearer test-api-key',
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'fileName' => 'test-file.png',
                        'fileType' => 'image/png',
                        'fileBasePath' => 'test-client-id/campaign',
                        'publicFile' => true
                    ]
                ]
            )
            ->andReturn($mockResponse);

        // Call the method
        $result = $this->mms->getSignedUploadUrl(
            'test-file.png',
            'image/png'
        );

        // Verify result
        $this->assertEquals(
            'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
            $result['signedS3Url']
        );
        $this->assertEquals(
            'test-client-id/campaign/test-file.png',
            $result['fileKey']
        );
    }

    /**
     * Test getting a signed upload URL with custom base path
     */
    public function testGetSignedUploadUrlWithCustomBasePath(): void
    {
        // Mock response
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'signedS3Url' => 'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
                'fileKey' => 'custom/path/test-file.png'
            ])
        );

        // Set up expectations
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://files.cloudcontactai.com/upload/url',
                [
                    'headers' => [
                        'Authorization' => 'Bearer test-api-key',
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'fileName' => 'test-file.png',
                        'fileType' => 'image/png',
                        'fileBasePath' => 'custom/path',
                        'publicFile' => true
                    ]
                ]
            )
            ->andReturn($mockResponse);

        // Call the method
        $result = $this->mms->getSignedUploadUrl(
            'test-file.png',
            'image/png',
            'custom/path'
        );

        // Verify result
        $this->assertEquals(
            'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
            $result['signedS3Url']
        );
        $this->assertEquals(
            'test-client-id/campaign/test-file.png',
            $result['fileKey']
        );
    }

    /**
     * Test validation for getSignedUploadUrl
     */
    public function testGetSignedUploadUrlValidation(): void
    {
        // Test empty file name
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File name is required');
        $this->mms->getSignedUploadUrl('', 'image/png');

        // Test empty file type
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File type is required');
        $this->mms->getSignedUploadUrl('test-file.png', '');
    }

    /**
     * Test error handling for getSignedUploadUrl
     */
    public function testGetSignedUploadUrlError(): void
    {
        // Mock error response
        $this->httpClient->shouldReceive('request')
            ->once()
            ->andThrow(new \Exception('API error'));

        // Expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get signed upload URL: API error');

        // Call the method
        $this->mms->getSignedUploadUrl('test-file.png', 'image/png');
    }

    /**
     * Test uploading an image to a signed URL
     */
    public function testUploadImageToSignedUrl(): void
    {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'mms_test_');
        file_put_contents($tempFile, 'test image content');

        // Mock response
        $mockResponse = new Response(200);

        // Set up expectations
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'PUT',
                'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
                [
                    'headers' => [
                        'Content-Type' => 'image/png'
                    ],
                    'body' => 'test image content'
                ]
            )
            ->andReturn($mockResponse);

        // Call the method
        $result = $this->mms->uploadImageToSignedUrl(
            'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
            $tempFile,
            'image/png'
        );

        // Verify result
        $this->assertTrue($result);

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test validation for uploadImageToSignedUrl
     */
    public function testUploadImageToSignedUrlValidation(): void
    {
        // Test empty signed URL
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Signed URL is required');
        $this->mms->uploadImageToSignedUrl('', '/path/to/file.png', 'image/png');

        // Test empty file path
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File path is required');
        $this->mms->uploadImageToSignedUrl('https://example.com', '', 'image/png');

        // Test non-existent file
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist:');
        $this->mms->uploadImageToSignedUrl('https://example.com', '/non/existent/file.png', 'image/png');

        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'mms_test_');

        // Test empty content type
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Content type is required');
        $this->mms->uploadImageToSignedUrl('https://example.com', $tempFile, '');

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test sending an MMS message
     */
    public function testSend(): void
    {
        // Sample account
        $account = new Account(
            'John',
            'Doe',
            '+15551234567'
        );

        // Sample message and title
        $message = 'Hello ${firstName}, check out this image!';
        $title = 'MMS Test Campaign';
        $pictureFileKey = 'test-client-id/campaign/test-image.png';

        // Mock response
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => 'msg-123',
                'status' => 'sent',
                'campaignId' => 'camp-456',
                'messagesSent' => 1,
                'timestamp' => '2025-06-06T12:00:00Z'
            ])
        );

        // Set up expectations
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://core.cloudcontactai.com/api/clients/test-client-id/campaigns/direct',
                [
                    'headers' => [
                        'Authorization' => 'Bearer test-api-key',
                        'Content-Type' => 'application/json',
                        'ForceNewCampaign' => 'true'
                    ],
                    'json' => [
                        'pictureFileKey' => $pictureFileKey,
                        'accounts' => [
                            [
                                'firstName' => 'John',
                                'lastName' => 'Doe',
                                'phone' => '+15551234567'
                            ]
                        ],
                        'message' => $message,
                        'title' => $title
                    ],
                    'timeout' => 30
                ]
            )
            ->andReturn($mockResponse);

        // Call the method
        $response = $this->mms->send(
            $pictureFileKey,
            [$account],
            $message,
            $title
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
        $this->assertEquals('camp-456', $response->campaignId);
        $this->assertEquals(1, $response->messagesSent);
        $this->assertEquals('2025-06-06T12:00:00Z', $response->timestamp);
    }

    /**
     * Test sending an MMS message with options
     */
    public function testSendWithOptions(): void
    {
        // Sample account
        $account = new Account(
            'John',
            'Doe',
            '+15551234567'
        );

        // Sample message and title
        $message = 'Hello ${firstName}, check out this image!';
        $title = 'MMS Test Campaign';
        $pictureFileKey = 'test-client-id/campaign/test-image.png';

        // Create progress tracking callback
        $progressUpdates = [];
        $progressCallback = function (string $status) use (&$progressUpdates) {
            $progressUpdates[] = $status;
        };

        // Create options
        $options = new SMSOptions(
            60,
            null,
            $progressCallback
        );

        // Mock response
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => 'msg-123',
                'status' => 'sent',
                'campaignId' => 'camp-456'
            ])
        );

        // Set up expectations
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://core.cloudcontactai.com/api/clients/test-client-id/campaigns/direct',
                [
                    'headers' => [
                        'Authorization' => 'Bearer test-api-key',
                        'Content-Type' => 'application/json',
                        'ForceNewCampaign' => 'true'
                    ],
                    'json' => [
                        'pictureFileKey' => $pictureFileKey,
                        'accounts' => [
                            [
                                'firstName' => 'John',
                                'lastName' => 'Doe',
                                'phone' => '+15551234567'
                            ]
                        ],
                        'message' => $message,
                        'title' => $title
                    ],
                    'timeout' => 60
                ]
            )
            ->andReturn($mockResponse);

        // Call the method
        $response = $this->mms->send(
            $pictureFileKey,
            [$account],
            $message,
            $title,
            $options
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
        $this->assertEquals('camp-456', $response->campaignId);

        // Verify progress updates
        $this->assertEquals([
            'Preparing to send MMS',
            'Sending MMS',
            'MMS sent successfully'
        ], $progressUpdates);
    }

    /**
     * Test sending a single MMS message
     */
    public function testSendSingle(): void
    {
        // Sample message and title
        $message = 'Hello ${firstName}, check out this image!';
        $title = 'MMS Test Campaign';
        $pictureFileKey = 'test-client-id/campaign/test-image.png';

        // Mock response
        $mockResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => 'msg-123',
                'status' => 'sent',
                'campaignId' => 'camp-456'
            ])
        );

        // Set up expectations
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://core.cloudcontactai.com/api/clients/test-client-id/campaigns/direct',
                Mockery::on(function ($arg) use ($pictureFileKey, $message, $title) {
                    $json = $arg['json'];
                    return $json['pictureFileKey'] === $pictureFileKey &&
                           $json['message'] === $message &&
                           $json['title'] === $title &&
                           $json['accounts'][0]['firstName'] === 'Jane' &&
                           $json['accounts'][0]['lastName'] === 'Smith' &&
                           $json['accounts'][0]['phone'] === '+15559876543';
                })
            )
            ->andReturn($mockResponse);

        // Call the method
        $response = $this->mms->sendSingle(
            $pictureFileKey,
            'Jane',
            'Smith',
            '+15559876543',
            $message,
            $title
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
        $this->assertEquals('camp-456', $response->campaignId);
    }

    /**
     * Test the complete MMS workflow
     */
    public function testSendWithImage(): void
    {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'mms_test_');
        file_put_contents($tempFile, 'test image content');
        
        // Sample account
        $account = new Account(
            'John',
            'Doe',
            '+15551234567'
        );

        // Sample message and title
        $message = 'Hello ${firstName}, check out this image!';
        $title = 'MMS Test Campaign';
        
        // Mock getSignedUploadUrl response
        $mockUploadUrlResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'signedS3Url' => 'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
                'fileKey' => 'test-client-id/campaign/test-file.png'
            ])
        );
        
        // Mock uploadImageToSignedUrl response
        $mockUploadResponse = new Response(200);
        
        // Mock send response
        $mockSendResponse = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => 'msg-123',
                'status' => 'sent',
                'campaignId' => 'camp-456'
            ])
        );
        
        // Set up expectations for getSignedUploadUrl
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://files.cloudcontactai.com/upload/url',
                Mockery::on(function ($arg) {
                    return isset($arg['json']['fileName']) && 
                           isset($arg['json']['fileType']) && 
                           $arg['json']['fileType'] === 'image/png';
                })
            )
            ->andReturn($mockUploadUrlResponse);
            
        // Set up expectations for uploadImageToSignedUrl
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'PUT',
                'https://s3.amazonaws.com/test-bucket/test-file.png?signature=abc123',
                Mockery::on(function ($arg) {
                    return isset($arg['headers']['Content-Type']) && 
                           $arg['headers']['Content-Type'] === 'image/png';
                })
            )
            ->andReturn($mockUploadResponse);
            
        // Set up expectations for send
        $this->httpClient->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                'https://core.cloudcontactai.com/api/clients/test-client-id/campaigns/direct',
                Mockery::on(function ($arg) {
                    return isset($arg['json']['pictureFileKey']) && 
                           $arg['json']['pictureFileKey'] === 'test-client-id/campaign/test-file.png';
                })
            )
            ->andReturn($mockSendResponse);
        
        // Create progress tracking callback
        $progressUpdates = [];
        $progressCallback = function (string $status) use (&$progressUpdates) {
            $progressUpdates[] = $status;
        };

        // Create options
        $options = new SMSOptions(
            null,
            null,
            $progressCallback
        );
        
        // Call the method
        $response = $this->mms->sendWithImage(
            $tempFile,
            'image/png',
            [$account],
            $message,
            $title,
            $options
        );
        
        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
        $this->assertEquals('camp-456', $response->campaignId);
        
        // Verify progress updates
        $this->assertContains('Getting signed upload URL', $progressUpdates);
        $this->assertContains('Uploading image to S3', $progressUpdates);
        $this->assertContains('Image uploaded successfully, sending MMS', $progressUpdates);
        
        // Clean up
        unlink($tempFile);
    }
}
