<?php

/**
 * SMS.php - SMS service for the CCAI API
 * Handles sending SMS messages through the Cloud Contact AI platform.
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\SMS;

use CloudContactAI\CCAI\CCAI;
use InvalidArgumentException;

/**
 * SMS service for sending messages through the CCAI API
 */
class SMS
{
    /**
     * @var CCAI The parent CCAI instance
     */
    private CCAI $ccai;

    /**
     * Create a new SMS service instance
     *
     * @param CCAI $ccai The parent CCAI instance
     */
    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Send an SMS message to one or more recipients
     *
     * @param array $accounts Array of Account objects or arrays
     * @param string $message Message content (can include ${firstName} and ${lastName} variables)
     * @param string $title Campaign title
     * @param SMSOptions|null $options Optional settings for the SMS send operation
     * 
     * @return SMSResponse API response
     * 
     * @throws InvalidArgumentException If required parameters are missing or invalid
     */
    public function send(array $accounts, string $message, string $title, ?SMSOptions $options = null): SMSResponse
    {
        // Validate inputs
        if (empty($accounts)) {
            throw new InvalidArgumentException('At least one account is required');
        }

        if (empty($message)) {
            throw new InvalidArgumentException('Message is required');
        }

        if (empty($title)) {
            throw new InvalidArgumentException('Campaign title is required');
        }

        // Create options if not provided
        $options = $options ?? new SMSOptions();

        // Normalize accounts
        $normalizedAccounts = [];
        foreach ($accounts as $index => $account) {
            if ($account instanceof Account) {
                $normalizedAccounts[] = $account;
            } elseif (is_array($account)) {
                try {
                    $normalizedAccounts[] = Account::fromArray($account);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid account at index %d: %s', $index, $e->getMessage())
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    sprintf('Invalid account at index %d: must be an Account object or array', $index)
                );
            }
        }

        // Notify progress if callback provided
        $options->notifyProgress('Preparing to send SMS');

        // Prepare the endpoint and data
        $endpoint = '/clients/' . $this->ccai->getClientId() . '/campaigns/direct';

        // Convert Account objects to arrays for API compatibility
        $accountsData = array_map(
            fn(Account $account) => $account->toArray(),
            $normalizedAccounts
        );

        $campaignData = [
            'accounts' => $accountsData,
            'message' => $message,
            'title' => $title
        ];

        try {
            // Notify progress if callback provided
            $options->notifyProgress('Sending SMS');

            // Make the API request
            $responseData = $this->ccai->request(
                'POST',
                $endpoint,
                $campaignData,
                $options->timeout ?? 30
            );

            // Notify progress if callback provided
            $options->notifyProgress('SMS sent successfully');

            // Convert response to SMSResponse object
            return new SMSResponse($responseData);
        } catch (\Exception $e) {
            // Notify progress if callback provided
            $options->notifyProgress('SMS sending failed');

            throw $e;
        }
    }

    /**
     * Send a single SMS message to one recipient
     *
     * @param string $firstName Recipient's first name
     * @param string $lastName Recipient's last name
     * @param string $phone Recipient's phone number (E.164 format)
     * @param string $message Message content (can include ${firstName} and ${lastName} variables)
     * @param string $title Campaign title
     * @param SMSOptions|null $options Optional settings for the SMS send operation
     * 
     * @return SMSResponse API response
     */
    public function sendSingle(
        string $firstName,
        string $lastName,
        string $phone,
        string $message,
        string $title,
        ?SMSOptions $options = null
    ): SMSResponse {
        $account = new Account($firstName, $lastName, $phone);

        return $this->send([$account], $message, $title, $options);
    }
}
