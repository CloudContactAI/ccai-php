<?php

/**
 * Account.php - Account model for the CCAI API
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\SMS;

/**
 * Account model representing a recipient
 */
class Account
{
    /**
     * Create a new Account instance
     *
     * @param string      $firstName    Recipient's first name
     * @param string      $lastName     Recipient's last name
     * @param string      $phone        Recipient's phone number in E.164 format
     * @param array|null  $data       Additional key-value pairs for variable substitution.
     *                                Use ${key} in your message. Sent to the API as "data".
     * @param string|null $customData   Arbitrary string forwarded as-is to your webhook handler.
     *                                  Not used in the message body. Sent to the API as "messageData".
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $phone,
        public readonly ?array $data = null,
        public readonly ?string $customData = null
    ) {
    }

    /**
     * Create an Account from an array
     *
     * @param array $data Account data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $firstName = $data['firstName'] ?? $data['first_name'] ?? '';
        $lastName = $data['lastName'] ?? $data['last_name'] ?? '';
        $phone = $data['phone'] ?? '';

        if (empty($firstName)) {
            throw new \InvalidArgumentException('First name is required');
        }

        if (empty($lastName)) {
            throw new \InvalidArgumentException('Last name is required');
        }

        if (empty($phone)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        return new self(
            $firstName,
            $lastName,
            $phone,
            $data['data'] ?? null,
            $data['customData'] ?? null
        );
    }

    /**
     * Convert the account to an array for the API wire format.
     *
     * @return array
     */
    public function toArray(): array
    {
        $arr = [
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'phone'     => $this->phone,
        ];
        if ($this->data !== null) {
            $arr['data'] = $this->data;
        }
        if ($this->customData !== null) {
            $arr['messageData'] = $this->customData;
        }
        return $arr;
    }
}
