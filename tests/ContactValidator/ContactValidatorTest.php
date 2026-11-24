<?php

/**
 * Tests for the Contact Validator service: email validation with Amazon SES.
 *
 * The responses in tests/ContactValidator/fixtures were recorded from the contact-validator API.
 *
 * @license MIT
 * @copyright 2026 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Tests\ContactValidator;

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\ContactValidator\ContactValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ContactValidatorTest extends TestCase
{
    /** @var array[] The requests Guzzle sent */
    private array $sent = [];

    private function recorded(string $name): array
    {
        $json = file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function recordedJson(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');
    }

    /** A validator whose HTTP client answers with the given responses, in order */
    private function validatorReplying(Response ...$responses): ContactValidator
    {
        $this->sent = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->sent));
        $ccai = new CCAI(['clientId' => 'test-client-id', 'apiKey' => 'test-api-key'], new Client(['handler' => $stack]));

        return $ccai->contactValidator;
    }

    private function ok(string $fixture): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], $this->recordedJson($fixture));
    }

    private function failure(int $status, string $fixture): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], $this->recordedJson($fixture));
    }

    public function testValidateEmailPostsTheAddressToTheEmailEndpoint(): void
    {
        $validator = $this->validatorReplying($this->ok('email-valid'));

        $validator->validateEmail('jane@example.com');

        $request = $this->sent[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/v1/contact-validator/email', (string) $request->getUri());
        $this->assertSame(['email' => 'jane@example.com'], json_decode((string) $request->getBody(), true));
    }

    public function testValidateEmailReturnsTheVerdictTheSixChecksTheDateAndReused(): void
    {
        $result = $this->validatorReplying($this->ok('email-valid'))->validateEmail('jane@example.com');

        $this->assertSame('valid', $result['status']);
        $this->assertSame('jane@example.com', $result['contactField']);
        $this->assertTrue($result['metadata']['safe_to_send']);
        $this->assertSame('valid', $result['metadata']['ai_verdict']);
        $this->assertSame('HIGH', $result['metadata']['verdict']);
        $this->assertSame([
            'hasValidSyntax'     => 'HIGH',
            'hasValidDnsRecords' => 'HIGH',
            'mailboxExists'      => 'HIGH',
            'isRoleAddress'      => 'LOW',
            'isDisposable'       => 'LOW',
            'isRandomInput'      => 'LOW',
        ], $result['metadata']['checks']);
        $this->assertSame('2026-11-13T15:02:11Z', $result['metadata']['validatedAt']);
        $this->assertFalse($result['metadata']['reused']);
    }

    public function testValidateEmailSaysWhenTheResultIsASavedCheck(): void
    {
        $result = $this->validatorReplying($this->ok('email-reused'))->validateEmail('jane@example.com');

        $this->assertTrue($result['metadata']['reused']);
    }

    public function testADisposableAddressIsRisky(): void
    {
        $result = $this->validatorReplying($this->ok('email-risky-disposable'))->validateEmail('temp@mailinator.com');

        $this->assertSame('risky', $result['status']);
        $this->assertFalse($result['metadata']['safe_to_send']);
        $this->assertSame('HIGH', $result['metadata']['checks']['isDisposable']);
    }

    public function testALowAddressIsInvalid(): void
    {
        $result = $this->validatorReplying($this->ok('email-invalid-low'))->validateEmail('nobody@no-such-domain-example.xyz');

        $this->assertSame('invalid', $result['status']);
        $this->assertSame('LOW', $result['metadata']['verdict']);
        $this->assertSame('LOW', $result['metadata']['checks']['hasValidDnsRecords']);
    }

    public function testAnAddressRejectedBeforeAskingSesHasUnknownChecksNoDateAndTheReason(): void
    {
        $result = $this->validatorReplying($this->ok('email-rejected-format'))->validateEmail('not-an-email');

        $this->assertSame('invalid', $result['status']);
        $this->assertSame('UNKNOWN', $result['metadata']['verdict']);
        $this->assertSame(['UNKNOWN'], array_values(array_unique($result['metadata']['checks'])));
        $this->assertCount(6, $result['metadata']['checks']);
        $this->assertNull($result['metadata']['validatedAt']);
        $this->assertSame('Invalid email format', $result['metadata']['error']);
    }

    public function testAResponseWithoutTheNewFieldsIsReadAsBefore(): void
    {
        $result = $this->validatorReplying($this->ok('email-legacy'))->validateEmail('jane@example.com');

        $this->assertSame('valid', $result['status']);
        $this->assertTrue($result['metadata']['safe_to_send']);
        $this->assertSame('Mode: Sandbox', $result['metadata']['ai_verdict']);
        $this->assertArrayNotHasKey('verdict', $result['metadata']);
        $this->assertArrayNotHasKey('checks', $result['metadata']);
        $this->assertArrayNotHasKey('validatedAt', $result['metadata']);
        $this->assertArrayNotHasKey('reused', $result['metadata']);
    }

    public function testValidateEmailFailsWith403WhenEmailValidationIsNotEnabledForTheAccount(): void
    {
        $validator = $this->validatorReplying($this->failure(403, 'error-403'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API Error: 403');

        $validator->validateEmail('jane@example.com');
    }

    public function testThe403MessageSaysEmailValidationIsNotEnabled(): void
    {
        $validator = $this->validatorReplying($this->failure(403, 'error-403'));

        try {
            $validator->validateEmail('jane@example.com');
            $this->fail('A 403 should throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Email validation is not enabled for this account', $e->getMessage());
        }
    }

    public function testValidateEmailFailsWith502WhenSesRejectsTheCheckWithTheMessageFromAws(): void
    {
        $validator = $this->validatorReplying($this->failure(502, 'error-502'));

        try {
            $validator->validateEmail('jane@example.com');
            $this->fail('A 502 should throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('API Error: 502', $e->getMessage());
            $this->assertStringContainsString('ses:GetEmailAddressInsights', $e->getMessage());
        }
    }

    public function testValidateEmailsPostsTheAddressesAndReturnsTheNewFieldsOnEveryResult(): void
    {
        $validator = $this->validatorReplying($this->ok('emails-bulk'));
        $emails = ['jane@example.com', 'temp@mailinator.com', 'bad@example.com'];

        $bulk = $validator->validateEmails($emails);

        $request = $this->sent[0]['request'];
        $this->assertStringEndsWith('/v1/contact-validator/emails', (string) $request->getUri());
        $this->assertSame(['emails' => $emails], json_decode((string) $request->getBody(), true));
        $this->assertSame(['total' => 3, 'valid' => 1, 'invalid' => 1, 'risky' => 1], $bulk['summary']);
        [$reused, $risky, $failed] = $bulk['results'];
        $this->assertTrue($reused['metadata']['reused']);
        $this->assertSame('HIGH', $reused['metadata']['verdict']);
        $this->assertSame('MEDIUM', $risky['metadata']['verdict']);
        $this->assertSame('HIGH', $risky['metadata']['checks']['isDisposable']);
        $this->assertSame('2026-11-13T15:05:40Z', $risky['metadata']['validatedAt']);
        // An address SES failed to check comes back invalid with the reason, and none of the new fields
        $this->assertSame('invalid', $failed['status']);
        $this->assertSame('The domain is not allowed', $failed['metadata']['error']);
        $this->assertArrayNotHasKey('verdict', $failed['metadata']);
        $this->assertArrayNotHasKey('checks', $failed['metadata']);
    }

    public function testValidateEmailsFailsWith403WhenEmailValidationIsNotEnabledForTheAccount(): void
    {
        $validator = $this->validatorReplying($this->failure(403, 'error-403'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API Error: 403');

        $validator->validateEmails(['jane@example.com']);
    }

    public function testTheRecordedFixturesAreValidJson(): void
    {
        foreach (['email-valid', 'email-reused', 'email-risky-disposable', 'email-invalid-low', 'email-rejected-format',
                  'email-legacy', 'emails-bulk', 'error-403', 'error-502'] as $name) {
            $this->assertIsArray($this->recorded($name), $name);
        }
    }
}
