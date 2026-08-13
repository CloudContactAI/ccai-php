<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\EmailAccount;

// Initialize the client with TEST environment credentials
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY',
    'useTestEnvironment' => true
]);

echo "🧪 Testing Email Campaigns with TEST environment\n";
echo "📧 Sending email to andreas@allcode.com\n\n";

try {
    // Test 1: Send a single email
    echo "1. Testing single email...\n";

    $response = $ccai->email->sendSingle(
        firstName: 'Andreas',
        lastName: 'Garcia',
        email: 'andreas@allcode.com',
        subject: 'Test Email from PHP CCAI Library',
        htmlContent: '<h1>Hello Andreas!</h1><p>This is a test email from the PHP CCAI library using the test environment.</p><p>Best regards,<br>CCAI PHP Team</p>',
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
        new EmailAccount('Andreas', 'Garcia', 'andreas@allcode.com')
    ];

    $campaign = [
        'accounts'    => array_map(fn(EmailAccount $a) => $a->toArray(), $accounts),
        'subject'     => 'PHP Campaign Test',
        'title'       => 'Test Campaign from PHP',
        'message'     => '<h1>Hello ${firstName} ${lastName}!</h1><p>This is a test email campaign from PHP.</p><ul><li>Feature 1: Email campaigns</li><li>Feature 2: Variable substitution</li><li>Feature 3: HTML content</li></ul><p>Best regards,<br>The PHP Team</p>',
        'senderEmail' => 'campaign@allcode.com',
        'replyEmail'  => 'support@allcode.com',
        'senderName'  => 'CCAI PHP Campaign',
    ];

    $campaignResponse = $ccai->email->sendCampaign($campaign);

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
echo "🔑 Client ID: " . $ccai->getClientId() . "\n";
echo "📱 Target Email: andreas@allcode.com\n";
echo "✅ Email functionality tested!\n";
