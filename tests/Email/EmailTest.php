<?php

/**
 * Tests for the Email service
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Tests\Email;

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\Email;
use CloudContactAI\CCAI\Email\EmailAccount;
use Mockery;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    /**
     * @var CCAI Mock CCAI client
     */
    private $ccai;

    /**
     * @var Email Email service instance
     */
    private Email $email;

    protected function setUp(): void
    {
        $this->ccai = Mockery::mock(CCAI::class);
        $this->email = new Email($this->ccai);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test sending an email campaign to multiple recipients
     */
    public function testSend(): void
    {
        $accounts = [
            new EmailAccount('John', 'Doe', 'john@example.com'),
            new EmailAccount('Jane', 'Smith', 'jane@example.com'),
        ];

        $this->ccai->shouldReceive('emailRequest')
            ->once()
            ->with(
                'POST',
                '/campaigns',
                [
                    'accounts'     => [
                        ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john@example.com'],
                        ['firstName' => 'Jane', 'lastName' => 'Smith', 'email' => 'jane@example.com'],
                    ],
                    'subject'      => 'Hello from CCAI',
                    'title'        => 'Hello from CCAI',
                    'message'      => '<p>Hello!</p>',
                    'senderEmail'  => 'sender@example.com',
                    'replyEmail'   => 'reply@example.com',
                    'senderName'   => 'CCAI Team',
                    'campaignType' => 'EMAIL',
                    'addToList'    => 'noList',
                    'contactInput' => 'accounts',
                    'fromType'     => 'single',
                    'senders'      => [],
                ]
            )
            ->andReturn(['campaignId' => 'camp-001', 'status' => 'queued']);

        $response = $this->email->send(
            $accounts,
            'Hello from CCAI',
            '<p>Hello!</p>',
            'sender@example.com',
            'reply@example.com',
            'CCAI Team'
        );

        $this->assertEquals('camp-001', $response['campaignId']);
        $this->assertEquals('queued', $response['status']);
    }

    /**
     * Test that customAccountId and data are sent as customAccountId/data (API wire format)
     */
    public function testSendWithCustomAccountIdAndCustomFields(): void
    {
        $account = new EmailAccount(
            'John',
            'Doe',
            'john@example.com',
            'ext-id-123',
            ['tier' => 'gold', 'locale' => 'en-US']
        );

        $this->ccai->shouldReceive('emailRequest')
            ->once()
            ->with(
                'POST',
                '/campaigns',
                \Mockery::on(function ($payload) {
                    $acc = $payload['accounts'][0];
                    return $acc['firstName'] === 'John'
                        && $acc['email'] === 'john@example.com'
                        && $acc['customAccountId'] === 'ext-id-123'
                        && $acc['data'] === ['tier' => 'gold', 'locale' => 'en-US'];
                })
            )
            ->andReturn([
                'id' => '123',
                'status' => 'PENDING',
                'message' => 'Email sent',
                'responseId' => 'resp-xyz'
            ]);

        $response = $this->email->send(
            [$account],
            'Test Subject',
            '<p>Test</p>',
            'sender@example.com',
            'reply@example.com',
            'Sender'
        );

        $this->assertEquals('123', $response['id']);
        $this->assertEquals('Email sent', $response['message']);
        $this->assertEquals('resp-xyz', $response['responseId']);
    }

    /**
     * Test sending an email to a single recipient
     */
    public function testSendSingle(): void
    {
        $this->ccai->shouldReceive('emailRequest')
            ->once()
            ->with(
                'POST',
                '/campaigns',
                [
                    'accounts'     => [
                        ['firstName' => 'Alice', 'lastName' => 'Wonder', 'email' => 'alice@example.com', 'phone' => ''],
                    ],
                    'subject'      => 'Welcome',
                    'title'        => 'Welcome',
                    'message'      => '<p>Welcome Alice!</p>',
                    'senderEmail'  => 'no-reply@example.com',
                    'replyEmail'   => 'support@example.com',
                    'senderName'   => 'Support Team',
                    'campaignType' => 'EMAIL',
                    'addToList'    => 'noList',
                    'contactInput' => 'accounts',
                    'fromType'     => 'single',
                    'senders'      => [],
                ]
            )
            ->andReturn(['campaignId' => 'camp-002', 'status' => 'queued']);

        $response = $this->email->sendSingle(
            'Alice',
            'Wonder',
            'alice@example.com',
            'Welcome',
            '<p>Welcome Alice!</p>',
            null,
            'no-reply@example.com',
            'support@example.com',
            'Support Team'
        );

        $this->assertEquals('camp-002', $response['campaignId']);
    }

}
