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

echo "=== CCAI PHP Email Campaign Examples ===\n\n";

// Example 1: Send a single email using the new method
echo "1. Sending single email with new method...\n";
try {
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
    
    echo "Email sent successfully: " . json_encode($response) . "\n\n";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n\n";
}

// Example 2: Send an email campaign to multiple recipients
echo "2. Sending email campaign to multiple recipients...\n";
try {
    $accounts = [
        new Account('Andreas', 'Garcia', 'andreas@allcode.com'),
        new Account('Test', 'User', 'joel@allcode.com'),
        new Account('Jane', 'Smith', 'jane@example.com')
    ];
    
    $campaign = new EmailCampaign(
        subject: 'Monthly Newsletter',
        title: 'July 2025 Newsletter',
        message: '
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
        senderEmail: 'newsletter@allcode.com',
        replyEmail: 'support@allcode.com',
        senderName: 'AllCode Newsletter',
        accounts: $accounts
    );
    
    // Create options with progress tracking
    $options = new EmailOptions(
        timeout: 60,
        retries: 3,
        onProgress: function($status) {
            echo "  Progress: $status\n";
        }
    );
    
    $response = $ccai->email->sendCampaign($campaign, $options);
    echo "Email campaign sent successfully: " . json_encode($response) . "\n\n";
} catch (Exception $e) {
    echo "Error sending email campaign: " . $e->getMessage() . "\n\n";
}

// Example 3: Schedule an email campaign for future delivery
echo "3. Scheduling email campaign for future delivery...\n";
try {
    // Schedule for tomorrow at 10:00 AM
    $tomorrow = new DateTime('tomorrow 10:00:00');
    
    $accounts = [
        new Account('Andreas', 'Garcia', 'andreas@allcode.com')
    ];
    
    $scheduledCampaign = new EmailCampaign(
        subject: 'Upcoming Event Reminder',
        title: 'Event Reminder Campaign',
        message: '
            <h1>Reminder: Upcoming Event</h1>
            <p>Hello ${firstName},</p>
            <p>This is a reminder about our upcoming event tomorrow at 2:00 PM.</p>
            <p>We look forward to seeing you there!</p>
            <p>Best regards,<br>The Events Team</p>
        ',
        senderEmail: 'events@allcode.com',
        replyEmail: 'events@allcode.com',
        senderName: 'AllCode Events',
        accounts: $accounts
    );
    
    // Set scheduling parameters
    $scheduledCampaign->scheduledTimestamp = $tomorrow->format('c');
    $scheduledCampaign->scheduledTimezone = 'America/New_York';
    
    $response = $ccai->email->sendCampaign($scheduledCampaign);
    echo "Email campaign scheduled successfully: " . json_encode($response) . "\n\n";
} catch (Exception $e) {
    echo "Error scheduling email campaign: " . $e->getMessage() . "\n\n";
}

// Example 4: Send an email with HTML template
echo "4. Sending email with HTML template...\n";
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
        message: $htmlTemplate,
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