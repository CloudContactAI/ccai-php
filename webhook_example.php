<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

try {
    // Example 1: Register a webhook with auto-generated secret
    echo "1. Registering webhook (server will auto-generate secret)...\n";
    $webhook = $ccai->webhook->register([
        'url' => 'https://your-domain.com/api/ccai-webhook'
    ]);

    echo "Webhook registered with ID: {$webhook['id']}\n";
    echo "Auto-generated Secret Key: {$webhook['secretKey']}\n";

    // Example 2: List all webhooks
    echo "\n2. Listing all webhooks...\n";
    $webhooks = $ccai->webhook->list();
    echo "Found " . count($webhooks) . " webhooks\n";

    // Example 3: Register a webhook with custom secret
    echo "\n3. Registering webhook with custom secret...\n";
    $webhookCustom = $ccai->webhook->register([
        'url' => 'https://your-domain.com/api/ccai-webhook-v2',
        'secretKey' => 'my-custom-secret-key'
    ]);
    echo "Webhook with custom secret registered with ID: {$webhookCustom['id']}\n";

    // Example 4: Update webhook
    echo "\n4. Updating webhook...\n";
    $updatedWebhook = $ccai->webhook->update((string) $webhook['id'], [
        'url' => 'https://your-domain.com/api/ccai-webhook-v3'
    ]);
    echo "Webhook updated to URL: {$updatedWebhook['url']}\n";

    // Example 5: Delete webhook
    echo "\n5. Deleting webhook...\n";
    $result = $ccai->webhook->delete((string) $webhook['id']);
    if ($result['success']) {
        echo "Webhook deleted successfully\n";
    }

} catch (Exception $e) {
    echo "Webhook operations failed: " . $e->getMessage() . "\n";
}

// Example 6: Signature verification example
echo "\n6. Signature Verification Example...\n";

$clientId = $ccai->getClientId();
$webhookSecret = 'my-webhook-secret-key';
$webhookPayload = json_encode([
    'eventType' => 'message.sent',
    'eventHash' => 'abc123def456ghi789',
    'data' => [
        'To' => '+15559876543',
        'From' => '+15551234567',
        'Message' => 'Hello, this is a test message!',
        'CampaignId' => '12345',
        'CampaignTitle' => 'Test Campaign',
    ],
]);

// In a real handler, the signature comes from the X-CCAI-Signature header
// and eventHash comes from the parsed payload — this just demonstrates the call.
$event = $ccai->webhook->parseWebhookEvent($webhookPayload);
$signatureFromHeader = 'signature-value-from-x-ccai-signature-header';

$isValid = $ccai->webhook->verifySignature($signatureFromHeader, $clientId, $event['eventHash'], $webhookSecret);
echo "Signature verification result: " . ($isValid ? "VALID" : "INVALID") . "\n";

echo "Parsed event type: {$event['eventType']}\n";
echo "Campaign ID: {$event['data']['CampaignId']}\n";
echo "Message: {$event['data']['Message']}\n";

echo "\nWebhook functionality demonstration completed!\n";