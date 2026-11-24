<?php

/**
 * ContactValidator.php - Contact validation service for the CloudContactAI platform
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\ContactValidator;

use CloudContactAI\CCAI\CCAI;

/**
 * Service for validating email addresses and phone numbers
 */
class ContactValidator
{
    private CCAI $ccai;

    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Validate a single email address with Amazon SES
     *
     * Nothing is sent to the address. Besides `status` and `metadata.safe_to_send`, `metadata` has these optional
     * keys (missing when the account does not have Email Address Validation, or the address was not checked):
     *  - `verdict`: the overall verdict, 'HIGH', 'MEDIUM', 'LOW' or 'UNKNOWN'
     *  - `checks`: six checks (hasValidSyntax, hasValidDnsRecords, mailboxExists, isRoleAddress, isDisposable,
     *    isRandomInput), each with its own verdict
     *  - `validatedAt`: when SES checked the address (ISO 8601); null when the address was rejected without a check
     *  - `reused`: true when the result is a saved check from the last 30 days, instead of a new one
     *  - `error`: why the address was rejected or could not be checked, for example 'Invalid email format'
     *
     * @param string $email Email address to validate
     * @return array{
     *     contactField: string,
     *     type: string,
     *     status: string,
     *     metadata: array{
     *         safe_to_send?: bool,
     *         ai_verdict?: string|null,
     *         verdict?: string,
     *         checks?: array{
     *             hasValidSyntax?: string,
     *             hasValidDnsRecords?: string,
     *             mailboxExists?: string,
     *             isRoleAddress?: string,
     *             isDisposable?: string,
     *             isRandomInput?: string
     *         },
     *         validatedAt?: string|null,
     *         reused?: bool,
     *         error?: string
     *     }
     * }
     *
     * @throws \RuntimeException With 'API Error: 403 - ...' when Email Address Validation is not enabled for the
     *                           account, and 'API Error: 502 - ...' when SES rejects the check
     */
    public function validateEmail(string $email): array
    {
        return $this->ccai->request('POST', '/v1/contact-validator/email', ['email' => $email]);
    }

    /**
     * Validate multiple email addresses with Amazon SES (up to 10 per request)
     *
     * Every item of `results` is shaped like the result of validateEmail(). An address that SES fails to check comes
     * back as 'invalid', with the reason in `metadata.error`.
     *
     * @param string[] $emails List of email addresses to validate
     * @return array{results: array[], summary: array{total: int, valid: int, invalid: int, risky: int}}
     *
     * @throws \RuntimeException With 'API Error: 403 - ...' when Email Address Validation is not enabled for the account
     */
    public function validateEmails(array $emails): array
    {
        return $this->ccai->request('POST', '/v1/contact-validator/emails', ['emails' => $emails]);
    }

    /**
     * Validate a single phone number
     *
     * @param string      $phone       Phone number in E.164 format (e.g. +15551234567)
     * @param string|null $countryCode Optional ISO 3166-1 alpha-2 country code (e.g. "US")
     * @return array Validation result with contactField, type, status and metadata
     */
    public function validatePhone(string $phone, ?string $countryCode = null): array
    {
        $data = ['phone' => $phone];
        if ($countryCode !== null) {
            $data['countryCode'] = $countryCode;
        }
        return $this->ccai->request('POST', '/v1/contact-validator/phone', $data);
    }

    /**
     * Validate multiple phone numbers (up to 50)
     *
     * @param array[] $phones List of phone inputs, each with 'phone' and optional 'countryCode'
     * @return array Bulk validation results with summary
     */
    public function validatePhones(array $phones): array
    {
        return $this->ccai->request('POST', '/v1/contact-validator/phones', ['phones' => $phones]);
    }
}
