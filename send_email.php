<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\Account;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: '<YOUR_CLIENT_ID>',
    'apiKey' => getenv('CCAI_API_KEY') ?: '<YOUR_API_KEY>'
]);

// Send a single email
$response = $ccai->email->sendSingle(
    firstName: 'John',
    lastName: 'Doe',
    email: 'example@example.com',
    subject: 'Welcome to the Test Email Campaign',
    body: 'Hello ${firstName}, this is a test email!'
);

echo "Email sent with ID: " . ($response->id ?? 'N/A') . "\n";

// Send to multiple recipients
$accounts = [
    new Account('John', 'Doe', 'example@example.com'),
    new Account('Jane', 'Smith', 'another@example.com')
];

$campaignResponse = $ccai->email->send(
    accounts: $accounts,
    subject: 'Bulk Campaign Subject',
    body: 'Hello ${firstName} ${lastName}, this is a bulk test email!'
);

var_dump($campaignResponse);

if (empty($campaignResponse->campaignId)) {
    echo "Campaign is pending approval. ID will be assigned once approved.\n";
} else {
    echo "Campaign sent with ID: " . $campaignResponse->campaignId . "\n";
}
