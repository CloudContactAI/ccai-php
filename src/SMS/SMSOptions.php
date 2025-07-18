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
     * @var int|null Request timeout in seconds
     */
    public  ?int $timeout;
    
    /**
     * @var int|null Number of retry attempts
     */
    public  ?int $retries;
    
    /**
     * @var callable|null Callback for tracking progress
     */
    public $onProgress;

    /**
     * Create a new SMSOptions instance
     *
     * @param int|null $timeout Request timeout in seconds
     * @param int|null $retries Number of retry attempts
     * @param callable|null $onProgress Callback for tracking progress
     */
    public function __construct(
        ?int $timeout = null,
        ?int $retries = null,
        $onProgress = null
    ) {
        $this->timeout = $timeout;
        $this->retries = $retries;
        $this->onProgress = $onProgress;
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
        if ($this->onProgress !== null && is_callable($this->onProgress)) {
            call_user_func($this->onProgress, $status);
        }
    }
}
