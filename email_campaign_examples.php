<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\EmailAccount;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Example 1: Send HTML template email
echo "Example 1: Sending HTML template email...\n";

$htmlTemplate = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #f1f1f1; padding: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, ${firstName}!</h1>
    </div>
    <div class="content">
        <p>Thank you for joining our platform.</p>
        <p>Here are some resources to get you started:</p>
        <ul>
            <li><a href="https://example.com/docs">Documentation</a></li>
            <li><a href="https://example.com/tutorials">Tutorials</a></li>
            <li><a href="https://example.com/support">Support</a></li>
        </ul>
    </div>
    <div class="footer">
        <p>&copy; 2025 Your Company. All rights reserved.</p>
    </div>
</body>
</html>';

try {
    $response = $ccai->email->sendSingle(
        firstName: 'Andreas',
        lastName: 'Garcia',
        email: 'andreas@allcode.com',
        subject: 'Welcome to Our Platform',
        htmlContent: $htmlTemplate,
        senderEmail: 'welcome@allcode.com',
        replyEmail: 'support@allcode.com',
        senderName: 'AllCode',
        title: 'Welcome HTML Template Email'
    );

    echo "HTML template email sent successfully!\n";
    var_dump($response);
} catch (Exception $e) {
    echo "Error sending HTML template email: " . $e->getMessage() . "\n";
}

// Example 2: Send a campaign to multiple recipients
echo "\nExample 2: Sending campaign to multiple recipients...\n";

$accounts = [
    new EmailAccount('Andreas', 'Garcia', 'andreas@allcode.com'),
    new EmailAccount('Test', 'User', 'joel@allcode.com'),
    new EmailAccount('Another', 'User', 'test@example.com')
];

$campaign = [
    'accounts'    => array_map(fn(EmailAccount $a) => $a->toArray(), $accounts),
    'subject'     => 'Product Update Newsletter',
    'title'       => 'January 2025 Product Updates',
    'message'     => '<h1>Product Updates - January 2025</h1><p>Hello ${firstName} ${lastName},</p><p>Here are the latest updates to our product:</p><ul><li>New dashboard design</li><li>Enhanced reporting features</li><li>Mobile app improvements</li></ul><p>Thank you for being a valued customer!</p><p>Best regards,<br>The Product Team</p>',
    'senderEmail' => 'product@allcode.com',
    'replyEmail'  => 'support@allcode.com',
    'senderName'  => 'AllCode Product Team',
];

try {
    $response = $ccai->email->sendCampaign($campaign);
    echo "Campaign sent successfully!\n";
    var_dump($response);
} catch (Exception $e) {
    echo "Error sending campaign: " . $e->getMessage() . "\n";
}

echo "\nEmail campaign examples completed!\n";
