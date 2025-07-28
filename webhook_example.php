<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\WebhookConfig;
use CloudContactAI\CCAI\WebhookEventType;
use CloudContactAI\CCAI\Webhook;

// Initialize the client
$ccai = new CCAI([
    'clientId' => '2682',
    'apiKey' => 'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJpbmZvQGFsbGNvZGUuY29tIiwiaXNzIjoiY2xvdWRjb250YWN0IiwibmJmIjoxNzE5NDQwMjM2LCJpYXQiOjE3MTk0NDAyMzYsInJvbGUiOiJVU0VSIiwiY2xpZW50SWQiOjI2ODIsImlkIjoyNzY0LCJ0eXBlIjoiQVBJX0tFWSIsImtleV9yYW5kb21faWQiOiI1MGRiOTUzZC1hMjUxLTRmZjMtODI5Yi01NjIyOGRhOGE1YTAifQ.PKVjXYHdjBMum9cTgLzFeY2KIb9b2tjawJ0WXalsb8Bckw1RuxeiYKS1bw5Cc36_Rfmivze0T7r-Zy0PVj2omDLq65io0zkBzIEJRNGDn3gx_AqmBrJ3yGnz9s0WTMr2-F1TFPUByzbj1eSOASIKeI7DGufTA5LDrRclVkz32Oo'
]);

try {
    // Example 1: Register a webhook
    echo "1. Registering webhook...\n";
    $config = new WebhookConfig(
        url: 'https://your-domain.com/api/ccai-webhook',
        events: [WebhookEventType::MESSAGE_SENT, WebhookEventType::MESSAGE_RECEIVED],
        secret: 'your-webhook-secret'
    );
    
    $webhook = $ccai->webhook->register($config);
    echo "Webhook registered with ID: {$webhook->id}\n";
    
    // Example 2: List all webhooks
    echo "\n2. Listing all webhooks...\n";
    $webhooks = $ccai->webhook->list();
    echo "Found " . count($webhooks) . " webhooks\n";
    
    // Example 3: Update webhook
    echo "\n3. Updating webhook...\n";
    $updateData = [
        'events' => [WebhookEventType::MESSAGE_RECEIVED]
    ];
    $updatedWebhook = $ccai->webhook->update($webhook->id, $updateData);
    echo "Webhook updated successfully\n";
    
    // Example 4: Delete webhook
    echo "\n4. Deleting webhook...\n";
    $result = $ccai->webhook->delete($webhook->id);
    echo "Webhook deleted successfully\n";
    
} catch (Exception $e) {
    echo "Webhook operations failed (this is expected if webhook endpoints don't exist): " . $e->getMessage() . "\n";
}

// Example 5: Create webhook handler
echo "\n5. Creating webhook handler...\n";

$handlers = [
    'onMessageSent' => function($event) {
        echo "Message sent: {$event->message} to {$event->to}\n";
        echo "Campaign: {$event->campaign->title} (ID: {$event->campaign->id})\n";
    },
    'onMessageReceived' => function($event) {
        echo "Message received: {$event->message} from {$event->from}\n";
        echo "Campaign: {$event->campaign->title} (ID: {$event->campaign->id})\n";
    }
];

$webhookHandler = Webhook::createHandler($handlers);

// Example webhook payload for testing
$testPayload = [
    'type' => WebhookEventType::MESSAGE_SENT,
    'campaign' => [
        'id' => 12345,
        'title' => 'Test Campaign',
        'message' => 'Test message',
        'senderPhone' => '+15551234567',
        'createdAt' => '2025-01-17T10:00:00Z',
        'runAt' => '2025-01-17T10:00:00Z'
    ],
    'from' => '+15551234567',
    'to' => '+15559876543',
    'message' => 'Hello, this is a test message!'
];

echo "Testing webhook handler with sample payload...\n";
$result = $webhookHandler($testPayload);
var_dump($result);

echo "\nWebhook functionality demonstration completed!\n";