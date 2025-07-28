<?php

// Test script to send webhook events to our local server

echo "🧪 Testing webhook server with sample events...\n\n";

// Test payload based on your actual SMS campaign
$testPayload = [
    'type' => 'message.sent',
    'campaign' => [
        'id' => 7545088, // Your actual campaign ID
        'title' => 'Bulk Test Campaign',
        'message' => 'Hello ${firstName} ${lastName}, this is a test message!',
        'senderPhone' => '+15551234567',
        'createdAt' => '2025-07-28T17:45:03.190725Z',
        'runAt' => '2025-07-28T17:45:03.190725Z'
    ],
    'from' => '+15551234567',
    'to' => '+14156961732',
    'message' => 'Hello Andreas Garcia, this is a test message!'
];

$data = json_encode($testPayload);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/webhook');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "📤 Sending MESSAGE_SENT webhook to localhost:8080/webhook...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "❌ Error: " . curl_error($ch) . "\n";
    echo "💡 Make sure the webhook server is running:\n";
    echo "   php webhook_server.php\n";
} else {
    echo "✅ Response Code: $httpCode\n";
    echo "📥 Response: $response\n";
}

curl_close($ch);

// Test a received message event
sleep(1);

$receivedPayload = [
    'type' => 'message.received',
    'campaign' => [
        'id' => 7545088,
        'title' => 'Bulk Test Campaign',
        'message' => 'Hello ${firstName} ${lastName}, this is a test message!',
        'senderPhone' => '+15551234567',
        'createdAt' => '2025-07-28T17:45:03.190725Z',
        'runAt' => '2025-07-28T17:45:03.190725Z'
    ],
    'from' => '+14156961732',
    'to' => '+15551234567',
    'message' => 'Thanks for the message!'
];

$data = json_encode($receivedPayload);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/webhook');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "\n📤 Sending MESSAGE_RECEIVED webhook to localhost:8080/webhook...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "❌ Error: " . curl_error($ch) . "\n";
} else {
    echo "✅ Response Code: $httpCode\n";
    echo "📥 Response: $response\n";
}

curl_close($ch);

echo "\n🎉 Webhook testing completed!\n";