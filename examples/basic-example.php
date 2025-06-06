<?php

/**
 * Basic example using the CCAI PHP client
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\SMSResponse;

// Create a new CCAI client
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'API-KEY-TOKEN'
]);

// Example recipients
$accounts = [
    new Account(
        firstName: 'John',
        lastName: 'Doe',
        phone: '+15551234567'  // Use E.164 format
    )
];

// Alternative array format
$arrayAccounts = [
    [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'phone' => '+15551234567'  // Use E.164 format
    ]
];

// Message with variable placeholders
$message = 'Hello ${firstName} ${lastName}, this is a test message!';
$title = 'Test Campaign';

/**
 * Example of sending SMS messages
 *
 * @return array
 */
function sendMessages(): array
{
    global $ccai, $accounts, $message, $title;

    try {
        // Method 1: Send SMS to multiple recipients
        echo 'Sending campaign to multiple recipients...' . PHP_EOL;
        $campaignResponse = $ccai->sms->send(
            accounts: $accounts,
            message: $message,
            title: $title
        );
        echo 'SMS campaign sent successfully!' . PHP_EOL;
        print_r($campaignResponse->toArray());

        // Method 2: Send SMS to a single recipient
        echo PHP_EOL . 'Sending message to a single recipient...' . PHP_EOL;
        $singleResponse = $ccai->sms->sendSingle(
            firstName: 'Jane',
            lastName: 'Smith',
            phone: '+15559876543',
            message: 'Hi ${firstName}, thanks for your interest!',
            title: 'Single Message Test'
        );
        echo 'Single SMS sent successfully!' . PHP_EOL;
        print_r($singleResponse->toArray());

        return [
            'campaignResponse' => $campaignResponse->toArray(),
            'singleResponse' => $singleResponse->toArray()
        ];
    } catch (Exception $error) {
        echo 'Error sending SMS: ' . $error->getMessage() . PHP_EOL;
        throw $error;
    }
}

// Execute the function
try {
    $results = sendMessages();
    echo PHP_EOL . 'All messages sent successfully!' . PHP_EOL;
    echo PHP_EOL . 'Results: ' . json_encode($results) . PHP_EOL;
} catch (Exception $e) {
    echo PHP_EOL . 'Failed to send one or more messages.' . PHP_EOL;
}
