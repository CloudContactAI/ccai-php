<?php

/**
 * SMSResponse.php - Response model for SMS operations
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\SMS;

/**
 * Response from the SMS API
 */
class SMSResponse
{
    /**
     * @var string|null Message ID
     */
    public ?int $id = null;

    /**
     * @var string|null Message status
     */
    public ?string $status = null;

    /**
     * @var string|null Campaign ID
     */
    public ?string $campaignId = null;

    /**
     * @var int|null Number of messages sent
     */
    public ?int $messagesSent = null;

    /**
     * @var string|null Timestamp of the operation
     */
    public ?string $timestamp = null;

    /**
     * @var array Additional data from the API
     */
    private array $additionalData = [];

    /**
     * Create a new SMSResponse instance
     *
     * @param array $data Response data from the API
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->campaignId = $data['campaignId'] ?? $data['campaign_id'] ?? null;
        $this->messagesSent = $data['messagesSent'] ?? $data['messages_sent'] ?? null;
        $this->timestamp = $data['timestamp'] ?? null;

        // Store all data for access via __get
        $this->additionalData = $data;
    }

    /**
     * Magic getter for additional data
     *
     * @param string $name Property name
     * 
     * @return mixed Property value or null if not found
     */
    public function __get(string $name)
    {
        $camelCase = $name;
        $snakeCase = $this->camelToSnake($name);

        return $this->additionalData[$camelCase] 
            ?? $this->additionalData[$snakeCase] 
            ?? null;
    }

    /**
     * Convert camelCase to snake_case
     *
     * @param string $input camelCase string
     * 
     * @return string snake_case string
     */
    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Convert the response to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->additionalData;
    }
}
