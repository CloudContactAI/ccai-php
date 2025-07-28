<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

// Initialize CCAI client with your credentials
$ccai = new CCAI([
    'clientId' => '2682',
    'apiKey' => 'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJpbmZvQGFsbGNvZGUuY29tIiwiaXNzIjoiY2xvdWRjb250YWN0IiwibmJmIjoxNzE5NDQwMjM2LCJpYXQiOjE3MTk0NDAyMzYsInJvbGUiOiJVU0VSIiwiY2xpZW50SWQiOjI2ODIsImlkIjoyNzY0LCJ0eXBlIjoiQVBJX0tFWSIsImtleV9yYW5kb21faWQiOiI1MGRiOTUzZC1hMjUxLTRmZjMtODI5Yi01NjIyOGRhOGE1YTAifQ.PKVjXYHdjBMum9cTgLzFeY2KIb9b2tjawJ0WXalsb8Bckw1RuxeiYKS1bw5Cc36_Rfmivze0T7r-Zy0PVj2omDLq65io0zkBzIEJRNGDn3gx_AqmBrJ3yGnz9s0WTMr2-F1TFPUByzbj1eSOASIKeI7DGufTA5LDrRclVkz32Oo'
]);

echo "🚀 Sending test SMS to trigger webhook...\n";

try {
    $response = $ccai->sms->sendSingle(
        firstName: 'Andreas',
        lastName: 'Garcia',
        phone: '+14156961732',
        message: 'Hello ${firstName}! This is a webhook test message from PHP.',
        title: 'PHP Webhook Test Campaign'
    );
    
    echo "✅ SMS sent successfully!\n";
    echo "📱 Message ID: {$response->id}\n";
    echo "📊 Status: {$response->status}\n";
    echo "📡 Check your phone for the message!\n";
    echo "🔔 If you have webhooks configured, you should receive a webhook event.\n\n";
    
    // Show the full response
    echo "📋 Full Response:\n";
    var_dump($response);
    
} catch (Exception $error) {
    echo "❌ Error sending SMS: " . $error->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📖 WEBHOOK SETUP INSTRUCTIONS:\n";
echo str_repeat("=", 60) . "\n";

echo "
1. 🌐 To receive webhooks, you need a public URL endpoint
2. 🔧 Use ngrok to expose your local server:
   
   # Install ngrok (if not already installed)
   brew install ngrok
   
   # Start your webhook server
   php webhook_server.php
   
   # In another terminal, expose it publicly
   ngrok http 8080
   
3. 📝 Register your webhook URL with CCAI (when API is available)
4. 📨 When messages are sent, CCAI will POST webhook events to your URL

🔍 Example webhook payload you'll receive:
{
    \"type\": \"message.sent\",
    \"campaign\": {
        \"id\": {$response->id},
        \"title\": \"PHP Webhook Test Campaign\",
        \"message\": \"Hello Andreas! This is a webhook test message from PHP.\",
        \"senderPhone\": \"+15551234567\",
        \"createdAt\": \"" . date('c') . "\",
        \"runAt\": \"" . date('c') . "\"
    },
    \"from\": \"+15551234567\",
    \"to\": \"+14156961732\",
    \"message\": \"Hello Andreas! This is a webhook test message from PHP.\"
}
";

echo "\n💡 TIP: The webhook handler we created will process this payload when it arrives!\n";