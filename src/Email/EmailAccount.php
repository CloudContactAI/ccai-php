<?php

/**
 * EmailAccount.php - Represents an email recipient account
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Email;

/**
 * Represents an email recipient account
 */
class EmailAccount
{
    public string $firstName;
    public string $lastName;
    public string $email;

    /**
     * External ID to link this account to an external system.
     * Sent to the API as "customAccountId".
     */
    public ?string $customAccountId;

    /**
     * Additional key-value pairs for variable substitution in email templates.
     * Sent to the API as "data".
     */
    public ?array $data;

    /**
     * @param string      $firstName       Recipient's first name
     * @param string      $lastName        Recipient's last name
     * @param string      $email           Recipient's email address
     * @param string|null $customAccountId External ID for this account
     * @param array|null  $data    Template variable substitution data (sent as "data")
     */
    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        ?string $customAccountId = null,
        ?array $data = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->customAccountId = $customAccountId;
        $this->data = $data;
    }

    public function toArray(): array
    {
        $arr = [
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'email'     => $this->email,
        ];
        if ($this->customAccountId !== null) {
            $arr['customAccountId'] = $this->customAccountId;
        }
        if ($this->data !== null) {
            $arr['data'] = $this->data;
        }
        return $arr;
    }
}
