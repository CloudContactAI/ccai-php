<?php

/**
 * Contact.php - A PHP module for managing contact preferences via CloudContactAI
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Contact;

use CloudContactAI\CCAI\CCAI;

/**
 * Contact service for managing opt-out preferences
 */
class Contact
{
    private CCAI $ccai;

    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Set the do-not-text preference for a contact
     *
     * @param bool $doNotText Whether to opt the contact out of text messages
     * @param string|null $contactId Contact ID (optional if phone is provided)
     * @param string|null $phone Phone number (optional if contactId is provided)
     * @return array API response
     */
    public function setDoNotText(bool $doNotText, ?string $contactId = null, ?string $phone = null): array
    {
        $payload = [
            'clientId'  => $this->ccai->getClientId(),
            'doNotText' => $doNotText,
        ];

        if ($contactId !== null) {
            $payload['contactId'] = $contactId;
        }

        if ($phone !== null) {
            $payload['phone'] = $phone;
        }

        return $this->ccai->request('PUT', '/account/do-not-text', $payload);
    }
}
