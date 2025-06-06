<?php

/**
 * Example with progress tracking using the CCAI PHP client
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\SMSOptions;

// Create a new CCAI client
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'API-KEY-TOKEN'
]);

// Example recipient
$account = new Account(
    firstName: 'John',
    lastName: 'Doe',
    phone: '+15551234567'  // Use E.164 format
);

// Message with variable placeholders
$message = 'Hello ${firstName} ${lastName}, this is a test message with progress tracking!';
$title = 'Progress Tracking Test';

// Create a progress tracking callback
$progressCallback = function (string $status) {
    echo date('Y-m-d H:i:s') . ' - ' . $status . PHP_EOL;
};

// Create options with progress tracking
$options = new SMSOptions(
    timeout: 60,
    retries: 3,
    onProgress: $progressCallback
);

try {
    echo 'Starting SMS send with progress tracking...' . PHP_EOL;
    
    // Send SMS with progress tracking
    $response = $ccai->sms->send(
        accounts: [$account],
        message: $message,
        title: $title,
        options: $options
    );
    
    echo PHP_EOL . 'SMS sent successfully!' . PHP_EOL;
    echo 'Response ID: ' . $response->id . PHP_EOL;
    echo 'Status: ' . $response->status . PHP_EOL;
    
    if ($response->campaignId) {
        echo 'Campaign ID: ' . $response->campaignId . PHP_EOL;
    }
    
    if ($response->messagesSent) {
        echo 'Messages sent: ' . $response->messagesSent . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
