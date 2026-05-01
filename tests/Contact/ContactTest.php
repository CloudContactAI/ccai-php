<?php

/**
 * Tests for the Contact service
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Tests\Contact;

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Contact\Contact;
use Mockery;
use PHPUnit\Framework\TestCase;

class ContactTest extends TestCase
{
    /**
     * @var CCAI Mock CCAI client
     */
    private $ccai;

    /**
     * @var Contact Contact service instance
     */
    private Contact $contact;

    protected function setUp(): void
    {
        $this->ccai = Mockery::mock(CCAI::class);
        $this->ccai->shouldReceive('getClientId')->andReturn('test-client-id');
        $this->contact = new Contact($this->ccai);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test opt-out by contactId
     */
    public function testSetDoNotTextByContactId(): void
    {
        $this->ccai->shouldReceive('request')
            ->once()
            ->with('PUT', '/account/do-not-text', [
                'clientId'  => 'test-client-id',
                'doNotText' => true,
                'contactId' => 'contact-123',
            ])
            ->andReturn(['success' => true]);

        $response = $this->contact->setDoNotText(true, contactId: 'contact-123');

        $this->assertTrue($response['success']);
    }

    /**
     * Test opt-out by phone number
     */
    public function testSetDoNotTextByPhone(): void
    {
        $this->ccai->shouldReceive('request')
            ->once()
            ->with('PUT', '/account/do-not-text', [
                'clientId'  => 'test-client-id',
                'doNotText' => true,
                'phone'     => '+15551234567',
            ])
            ->andReturn(['success' => true]);

        $response = $this->contact->setDoNotText(true, phone: '+15551234567');

        $this->assertTrue($response['success']);
    }

    /**
     * Test opt-in (doNotText = false)
     */
    public function testSetDoNotTextOptIn(): void
    {
        $this->ccai->shouldReceive('request')
            ->once()
            ->with('PUT', '/account/do-not-text', [
                'clientId'  => 'test-client-id',
                'doNotText' => false,
                'contactId' => 'contact-456',
            ])
            ->andReturn(['success' => true]);

        $response = $this->contact->setDoNotText(false, contactId: 'contact-456');

        $this->assertTrue($response['success']);
    }

    /**
     * Test with both contactId and phone
     */
    public function testSetDoNotTextWithBothIdentifiers(): void
    {
        $this->ccai->shouldReceive('request')
            ->once()
            ->with('PUT', '/account/do-not-text', [
                'clientId'  => 'test-client-id',
                'doNotText' => true,
                'contactId' => 'contact-789',
                'phone'     => '+15559876543',
            ])
            ->andReturn(['success' => true]);

        $response = $this->contact->setDoNotText(true, contactId: 'contact-789', phone: '+15559876543');

        $this->assertTrue($response['success']);
    }

    /**
     * Test with no identifier (only doNotText flag)
     */
    public function testSetDoNotTextWithNoIdentifier(): void
    {
        $this->ccai->shouldReceive('request')
            ->once()
            ->with('PUT', '/account/do-not-text', [
                'clientId'  => 'test-client-id',
                'doNotText' => true,
            ])
            ->andReturn(['success' => true]);

        $response = $this->contact->setDoNotText(true);

        $this->assertTrue($response['success']);
    }
}
