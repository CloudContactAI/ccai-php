<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\EmailAccount;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

echo "=== CCAI PHP Email Campaign Examples ===\n\n";

// Example 1: Send a single email
echo "1. Sending single email...\n";
try {
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

    echo "Email sent successfully: " . json_encode($response) . "\n\n";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n\n";
}

// Example 2: Send an email campaign to multiple recipients
echo "2. Sending email campaign to multiple recipients...\n";
try {
    $accounts = [
        new EmailAccount('Andreas', 'Garcia', 'andreas@allcode.com'),
        new EmailAccount('Test', 'User', 'joel@allcode.com'),
        new EmailAccount('Jane', 'Smith', 'jane@example.com')
    ];

    $campaign = [
        'accounts'    => array_map(fn(EmailAccount $a) => $a->toArray(), $accounts),
        'subject'     => 'Monthly Newsletter',
        'title'       => 'July 2025 Newsletter',
        'message'     => '
            <h1>Monthly Newsletter - July 2025</h1>
            <p>Hello ${firstName},</p>
            <p>Here are our updates for this month:</p>
            <ul>
                <li>New feature: Email campaigns</li>
                <li>Improved performance</li>
                <li>Bug fixes</li>
            </ul>
            <p>Thank you for being a valued customer!</p>
            <p>Best regards,<br>The Team</p>
        ',
        'senderEmail' => 'newsletter@allcode.com',
        'replyEmail'  => 'support@allcode.com',
        'senderName'  => 'AllCode Newsletter',
    ];

    $response = $ccai->email->sendCampaign($campaign);
    echo "Email campaign sent successfully: " . json_encode($response) . "\n\n";
} catch (Exception $e) {
    echo "Error sending email campaign: " . $e->getMessage() . "\n\n";
}

// Example 3: Send an email with HTML template
echo "3. Sending email with HTML template...\n";
try {
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
                <p>&copy; 2025 AllCode. All rights reserved.</p>
            </div>
        </body>
        </html>
    ';

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

    echo "HTML template email sent successfully: " . json_encode($response) . "\n\n";
} catch (Exception $e) {
    echo "Error sending HTML template email: " . $e->getMessage() . "\n\n";
}

echo "=== Email Campaign Examples Complete ===\n";
