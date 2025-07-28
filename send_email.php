<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\Account;
use CloudContactAI\CCAI\Email\EmailCampaign;
use CloudContactAI\CCAI\Email\EmailOptions;

// Initialize the client
$ccai = new CCAI([
    'clientId' => '2682',
    'apiKey' => 'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJpbmZvQGFsbGNvZGUuY29tIiwiaXNzIjoiY2xvdWRjb250YWN0IiwibmJmIjoxNzE5NDQwMjM2LCJpYXQiOjE3MTk0NDAyMzYsInJvbGUiOiJVU0VSIiwiY2xpZW50SWQiOjI2ODIsImlkIjoyNzY0LCJ0eXBlIjoiQVBJX0tFWSIsImtleV9yYW5kb21faWQiOiI1MGRiOTUzZC1hMjUxLTRmZjMtODI5Yi01NjIyOGRhOGE1YTAifQ.PKVjXYHdjBMum9cTgLzFeY2KIb9b2tjawJ0WXalsb8Bckw1RuxeiYKS1bw5Cc36_Rfmivze0T7r-Zy0PVj2omDLq65io0zkBzIEJRNGDn3gx_AqmBrJ3yGnz9s0WTMr2-F1TFPUByzbj1eSOASIKeI7DGufTA5LDrRclVkz32Oo'
]);

// Send a single email using new enhanced method
$response = $ccai->email->sendSingle(
    firstName: 'Andreas',
    lastName: 'Garcia',
    email: 'andreas@allcode.com',
    subject: 'Welcome to Our Service',
    message: '<p>Hello Andreas,</p><p>Thank you for signing up for our service!</p><p>Best regards,<br>AllCode Team</p>',
    senderEmail: 'noreply@allcode.com',
    replyEmail: 'support@allcode.com',
    senderName: 'AllCode',
    title: 'Welcome Email'
);

echo "Single email sent successfully!\n";
var_dump($response);

// Send email campaign to multiple recipients
$accounts = [
    new Account('Andreas', 'Garcia', 'andreas@allcode.com'),
    new Account('Test', 'User', 'joel@allcode.com')
];

$campaign = new EmailCampaign(
    subject: 'Monthly Newsletter',
    title: 'July 2025 Newsletter',
    message: '<h1>Hello ${firstName} ${lastName}!</h1><p>Here are our updates for this month:</p><ul><li>New email campaigns</li><li>Improved performance</li></ul>',
    senderEmail: 'newsletter@allcode.com',
    replyEmail: 'support@allcode.com',
    senderName: 'AllCode Newsletter',
    accounts: $accounts
);

// Add progress tracking
$options = new EmailOptions(
    timeout: 60,
    retries: 3,
    onProgress: function($status) {
        echo "Progress: $status\n";
    }
);

$campaignResponse = $ccai->email->sendCampaign($campaign, $options);

echo "Email campaign sent successfully!\n";
var_dump($campaignResponse);
