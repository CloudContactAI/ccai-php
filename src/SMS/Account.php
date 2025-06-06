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
     * @param string $firstName Recipient's first name
     * @param string $lastName Recipient's last name
     * @param string $phone Recipient's phone number in E.164 format
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $phone
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

        return new self($firstName, $lastName, $phone);
    }

    /**
     * Convert the account to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phone' => $this->phone
        ];
    }
}
