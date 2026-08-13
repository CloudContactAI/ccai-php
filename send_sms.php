<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Send a single SMS
$response = $ccai->sms->sendSingle(
    firstName: 'Andreas',
    lastName: 'Garcia',
    phone: '+14156961732',
    message: 'Hello ${firstName}, this is a test message!',
    title: 'Test Campaign'
);

echo "Message sent with ID: " . $response->id . "\n";

// Send to multiple recipients
$accounts = [
    new Account('Andreas', 'Garcia', '+14156961732'),
    new Account('Jane', 'Smith', '+14156961732')
];

$campaignResponse = $ccai->sms->send(
    accounts: $accounts,
    message: 'Hello ${firstName} ${lastName}, this is a test message!',
    title: 'Bulk Test Campaign'
);
var_dump($campaignResponse);

if ($campaignResponse->campaignId === null) {
    echo "Campaign is pending approval. ID will be assigned once approved.\n";
} else {
    echo "Campaign sent with ID: " . $campaignResponse->campaignId . "\n";
}


