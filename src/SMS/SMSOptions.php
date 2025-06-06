<?php

/**
 * SMSOptions.php - Options for SMS operations
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\SMS;

/**
 * Options for SMS operations
 */
class SMSOptions
{
    /**
     * Create a new SMSOptions instance
     *
     * @param int|null $timeout Request timeout in seconds
     * @param int|null $retries Number of retry attempts
     * @param callable|null $onProgress Callback for tracking progress
     */
    public function __construct(
        public readonly ?int $timeout = null,
        public readonly ?int $retries = null,
        public readonly ?callable $onProgress = null
    ) {
    }

    /**
     * Notify progress if callback is provided
     *
     * @param string $status Progress status
     * 
     * @return void
     */
    public function notifyProgress(string $status): void
    {
        if ($this->onProgress !== null) {
            call_user_func($this->onProgress, $status);
        }
    }
}
