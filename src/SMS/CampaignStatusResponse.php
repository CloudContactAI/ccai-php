<?php

/**
 * CampaignStatusResponse.php - Response model for campaign status queries
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\SMS;

/**
 * Response from the campaign status API
 */
class CampaignStatusResponse
{
    /**
     * @var string Campaign ID
     */
    public string $id;

    /**
     * @var string Campaign status (e.g. 'completed', 'in_progress', 'failed')
     */
    public string $status;

    /**
     * @var int Total number of messages in the campaign
     */
    public int $totalMessages;

    /**
     * @var int Number of messages sent successfully
     */
    public int $sentMessages;

    /**
     * @var int Number of messages that failed
     */
    public int $failedMessages;

    /**
     * @var array Additional data from the API
     */
    private array $additionalData = [];

    /**
     * Create a new CampaignStatusResponse instance
     *
     * @param array $data Response data from the API
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->totalMessages = (int) ($data['totalMessages'] ?? $data['total_messages'] ?? 0);
        $this->sentMessages = (int) ($data['sentMessages'] ?? $data['sent_messages'] ?? 0);
        $this->failedMessages = (int) ($data['failedMessages'] ?? $data['failed_messages'] ?? 0);

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
