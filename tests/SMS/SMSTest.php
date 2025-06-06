<?php

/**
 * Tests for the SMS service
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Tests\SMS;

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\SMSOptions;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class SMSTest extends TestCase
{
    /**
     * @var CCAI Mock CCAI client
     */
    private $ccai;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->ccai = Mockery::mock(CCAI::class);
        $this->ccai->shouldReceive('getClientId')->andReturn('test-client-id');
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test sending SMS to multiple recipients
     */
    public function testSend(): void
    {
        // Sample account
        $account = new Account(
            firstName: 'John',
            lastName: 'Doe',
            phone: '+15551234567'
        );

        // Sample message and title
        $message = 'Hello ${firstName}, this is a test message!';
        $title = 'Test Campaign';

        // Mock response
        $this->ccai->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                '/clients/test-client-id/campaigns/direct',
                [
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
                30
            )
            ->andReturn([
                'id' => 'msg-123',
                'status' => 'sent',
                'campaignId' => 'camp-456',
                'messagesSent' => 1,
                'timestamp' => '2025-06-06T12:00:00Z'
            ]);

        // Send SMS
        $response = $this->ccai->sms->send(
            accounts: [$account],
            message: $message,
            title: $title
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
        $this->assertEquals('camp-456', $response->campaignId);
        $this->assertEquals(1, $response->messagesSent);
        $this->assertEquals('2025-06-06T12:00:00Z', $response->timestamp);
    }

    /**
     * Test sending SMS with array accounts
     */
    public function testSendWithArrayAccounts(): void
    {
        // Sample message and title
        $message = 'Hello ${firstName}, this is a test message!';
        $title = 'Test Campaign';

        // Mock response
        $this->ccai->shouldReceive('request')
            ->once()
            ->andReturn([
                'id' => 'msg-123',
                'status' => 'sent'
            ]);

        // Send SMS with array accounts
        $response = $this->ccai->sms->send(
            accounts: [
                [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'phone' => '+15551234567'
                ]
            ],
            message: $message,
            title: $title
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
    }

    /**
     * Test sending SMS to a single recipient
     */
    public function testSendSingle(): void
    {
        // Sample message and title
        $message = 'Hi ${firstName}, thanks for your interest!';
        $title = 'Single Message Test';

        // Mock response
        $this->ccai->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                '/clients/test-client-id/campaigns/direct',
                [
                    'accounts' => [
                        [
                            'firstName' => 'Jane',
                            'lastName' => 'Smith',
                            'phone' => '+15559876543'
                        ]
                    ],
                    'message' => $message,
                    'title' => $title
                ],
                30
            )
            ->andReturn([
                'id' => 'msg-123',
                'status' => 'sent'
            ]);

        // Send SMS to a single recipient
        $response = $this->ccai->sms->sendSingle(
            firstName: 'Jane',
            lastName: 'Smith',
            phone: '+15559876543',
            message: $message,
            title: $title
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);
    }

    /**
     * Test sending SMS with options
     */
    public function testSendWithOptions(): void
    {
        // Sample account
        $account = new Account(
            firstName: 'John',
            lastName: 'Doe',
            phone: '+15551234567'
        );

        // Sample message and title
        $message = 'Hello ${firstName}, this is a test message!';
        $title = 'Test Campaign';

        // Create progress tracking callback
        $progressUpdates = [];
        $progressCallback = function (string $status) use (&$progressUpdates) {
            $progressUpdates[] = $status;
        };

        // Create options
        $options = new SMSOptions(
            timeout: 60,
            retries: 3,
            onProgress: $progressCallback
        );

        // Mock response
        $this->ccai->shouldReceive('request')
            ->once()
            ->with(
                'POST',
                '/clients/test-client-id/campaigns/direct',
                [
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
                60
            )
            ->andReturn([
                'id' => 'msg-123',
                'status' => 'sent'
            ]);

        // Send SMS with options
        $response = $this->ccai->sms->send(
            accounts: [$account],
            message: $message,
            title: $title,
            options: $options
        );

        // Verify response
        $this->assertEquals('msg-123', $response->id);
        $this->assertEquals('sent', $response->status);

        // Verify progress updates
        $this->assertEquals([
            'Preparing to send SMS',
            'Sending SMS',
            'SMS sent successfully'
        ], $progressUpdates);
    }

    /**
     * Test input validation
     */
    public function testValidation(): void
    {
        // Test empty accounts
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one account is required');
        $this->ccai->sms->send(
            accounts: [],
            message: 'Test message',
            title: 'Test Campaign'
        );

        // Test empty message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message is required');
        $this->ccai->sms->send(
            accounts: [new Account('John', 'Doe', '+15551234567')],
            message: '',
            title: 'Test Campaign'
        );

        // Test empty title
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign title is required');
        $this->ccai->sms->send(
            accounts: [new Account('John', 'Doe', '+15551234567')],
            message: 'Test message',
            title: ''
        );
    }
}
