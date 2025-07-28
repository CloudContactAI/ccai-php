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
        message: $htmlTemplate,
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

// Example 2: Schedule an email campaign for future delivery
echo "\nExample 2: Scheduling email campaign...\n";

// Schedule for tomorrow at 10:00 AM
$tomorrow = new DateTime('tomorrow 10:00:00');

$accounts = [
    new Account('Andreas', 'Garcia', 'andreas@allcode.com')
];

$scheduledCampaign = new EmailCampaign(
    subject: 'Upcoming Event Reminder',
    title: 'Event Reminder Campaign',
    message: '<h1>Reminder: Upcoming Event</h1><p>Hello ${firstName},</p><p>This is a reminder about our upcoming event tomorrow at 2:00 PM.</p><p>We look forward to seeing you there!</p><p>Best regards,<br>The Events Team</p>',
    senderEmail: 'events@allcode.com',
    replyEmail: 'events@allcode.com',
    senderName: 'AllCode Events',
    accounts: $accounts
);

// Set scheduling parameters
$scheduledCampaign->scheduledTimestamp = $tomorrow->format('c'); // ISO 8601 format
$scheduledCampaign->scheduledTimezone = 'America/New_York';

try {
    $response = $ccai->email->sendCampaign($scheduledCampaign);
    echo "Email campaign scheduled successfully!\n";
    var_dump($response);
} catch (Exception $e) {
    echo "Error scheduling email campaign: " . $e->getMessage() . "\n";
}

// Example 3: Send campaign with progress tracking
echo "\nExample 3: Sending campaign with progress tracking...\n";

$accounts = [
    new Account('Andreas', 'Garcia', 'andreas@allcode.com'),
    new Account('Test', 'User', 'joel@allcode.com'),
    new Account('Another', 'User', 'test@example.com')
];

$campaign = new EmailCampaign(
    subject: 'Product Update Newsletter',
    title: 'January 2025 Product Updates',
    message: '<h1>Product Updates - January 2025</h1><p>Hello ${firstName} ${lastName},</p><p>Here are the latest updates to our product:</p><ul><li>New dashboard design</li><li>Enhanced reporting features</li><li>Mobile app improvements</li></ul><p>Thank you for being a valued customer!</p><p>Best regards,<br>The Product Team</p>',
    senderEmail: 'product@allcode.com',
    replyEmail: 'support@allcode.com',
    senderName: 'AllCode Product Team',
    accounts: $accounts
);

$options = new EmailOptions(
    timeout: 60,
    retries: 3,
    onProgress: function($status) {
        echo "Status: $status\n";
    }
);

try {
    $response = $ccai->email->sendCampaign($campaign, $options);
    echo "Campaign with progress tracking sent successfully!\n";
    var_dump($response);
} catch (Exception $e) {
    echo "Error sending campaign: " . $e->getMessage() . "\n";
}

echo "\nEmail campaign examples completed!\n";