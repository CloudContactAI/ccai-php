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
     * Validate a single email address
     *
     * @param string $email Email address to validate
     * @return array Validation result with contactField, type, status and metadata
     */
    public function validateEmail(string $email): array
    {
        return $this->ccai->request('POST', '/v1/contact-validator/email', ['email' => $email]);
    }

    /**
     * Validate multiple email addresses (up to 50)
     *
     * @param string[] $emails List of email addresses to validate
     * @return array Bulk validation results with summary
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
