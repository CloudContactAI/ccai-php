<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\EmailAccount;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Send a single email
$response = $ccai->email->sendSingle(
    firstName: 'Andreas',
    lastName: 'Garcia',
    email: 'andreas@allcode.com',
    subject: 'Welcome to Our Service',
    htmlContent: '<p>Hello Andreas,</p><p>Thank you for signing up for our service!</p><p>Best regards,<br>AllCode Team</p>',
    senderEmail: 'noreply@allcode.com',
    replyEmail: 'support@allcode.com',
    senderName: 'AllCode',
    title: 'Welcome Email'
);

echo "Single email sent successfully!\n";
var_dump($response);

// Send email campaign to multiple recipients
$accounts = [
    new EmailAccount('Andreas', 'Garcia', 'andreas@allcode.com'),
    new EmailAccount('Test', 'User', 'joel@allcode.com')
];

$campaign = [
    'accounts'    => array_map(fn(EmailAccount $a) => $a->toArray(), $accounts),
    'subject'     => 'Monthly Newsletter',
    'title'       => 'July 2025 Newsletter',
    'message'     => '<h1>Hello ${firstName} ${lastName}!</h1><p>Here are our updates for this month:</p><ul><li>New email campaigns</li><li>Improved performance</li></ul>',
    'senderEmail' => 'newsletter@allcode.com',
    'replyEmail'  => 'support@allcode.com',
    'senderName'  => 'AllCode Newsletter',
];

$campaignResponse = $ccai->email->sendCampaign($campaign);

echo "Email campaign sent successfully!\n";
var_dump($campaignResponse);
