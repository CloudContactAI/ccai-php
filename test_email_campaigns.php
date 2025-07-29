<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\Account;
use CloudContactAI\CCAI\Email\EmailCampaign;
use CloudContactAI\CCAI\Email\EmailOptions;

// Initialize the client with TEST environment credentials
$ccai = new CCAI([
    'clientId' => '1231',
    'apiKey' => 'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJhbmRyZWFzQGFsbGNvZGUuY29tIiwiaXNzIjoiY2xvdWRjb250YWN0IiwibmJmIjoxNzUyMDg5MDk2LCJpYXQiOjE3NTIwODkwOTYsInJvbGUiOiJVU0VSIiwiY2xpZW50SWQiOjEyMzEsImlkIjoxMjIzLCJ0eXBlIjoiQVBJX0tFWSIsImtleV9yYW5kb21faWQiOiIzNTAxZjVmNC0zOWYyLTRjYzctYTk2Yi04ZDkyZjVlMjM5ZGUifQ.XjtDPpyYUJNJjLrpM1pdQ4Sqk90eaagqzPX2v1gwHDP1wOV4fTbB44UGDRXtWyGvN-Fz7o84_Ab-VlAjNCyEmXcDzmzscnwFSbqiZrWLAM_W3Mutd36vArl9QSG_osuYdf9T2wmAduUZu2bcnvKHdBbEaBUalJSSUoHwHsMBX3w',
    'baseUrl' => 'https://core-test-cloudcontactai.allcode.com/api'
]);

echo "🧪 Testing Email Campaigns with TEST environment\n";
echo "📧 Sending email to andreas@allcode.com\n\n";

try {
    // Test 1: Send a single email using the enhanced method
    echo "1. Testing single email with enhanced method...\n";
    
    $response = $ccai->email->sendSingle(
        firstName: 'Andreas',
        lastName: 'Garcia',
        email: 'andreas@allcode.com',
        subject: 'Test Email from PHP CCAI Library',
        message: '<h1>Hello Andreas!</h1><p>This is a test email from the PHP CCAI library using the test environment.</p><p>Best regards,<br>CCAI PHP Team</p>',
        senderEmail: 'test@allcode.com',
        replyEmail: 'support@allcode.com',
        senderName: 'CCAI PHP Test',
        title: 'PHP Test Email'
    );
    
    echo "✅ Single email sent successfully!\n";
    var_dump($response);
    
} catch (Exception $e) {
    echo "❌ Error sending single email: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n";

try {
    // Test 2: Send email campaign to multiple recipients
    echo "2. Testing email campaign...\n";
    
    $accounts = [
        new Account('Andreas', 'Garcia', 'andreas@allcode.com')
    ];
    
    $campaign = new EmailCampaign(
        subject: 'PHP Campaign Test',
        title: 'Test Campaign from PHP',
        message: '<h1>Hello ${firstName} ${lastName}!</h1><p>This is a test email campaign from PHP.</p><ul><li>Feature 1: Email campaigns</li><li>Feature 2: Variable substitution</li><li>Feature 3: HTML content</li></ul><p>Best regards,<br>The PHP Team</p>',
        senderEmail: 'campaign@allcode.com',
        replyEmail: 'support@allcode.com',
        senderName: 'CCAI PHP Campaign',
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
    
    echo "✅ Email campaign sent successfully!\n";
    var_dump($campaignResponse);
    
} catch (Exception $e) {
    echo "❌ Error sending email campaign: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 TEST ENVIRONMENT SUMMARY:\n";
echo str_repeat("=", 60) . "\n";
echo "🌐 Core API: https://core-test-cloudcontactai.allcode.com/api\n";
echo "📧 Email API: https://email-campaigns-test-cloudcontactai.allcode.com/api/v1\n";
echo "🔑 Client ID: 1231\n";
echo "📱 Target Email: andreas@allcode.com\n";
echo "✅ Email functionality tested!\n";