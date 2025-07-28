<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\WebhookEventType;
use CloudContactAI\CCAI\Webhook;
use CloudContactAI\CCAI\MessageSentEvent;
use CloudContactAI\CCAI\MessageReceivedEvent;

// Example webhook handler for different PHP frameworks

// 1. Simple PHP webhook handler (can be used in any framework)
function handleWebhookPayload($payload) {
    echo "Received webhook payload:\n";
    
    $eventType = $payload['type'] ?? null;
    
    if ($eventType === WebhookEventType::MESSAGE_SENT) {
        $event = new MessageSentEvent($payload);
        echo "✅ Message sent event:\n";
        echo "   Campaign: {$event->campaign->title} (ID: {$event->campaign->id})\n";
        echo "   From: {$event->from}\n";
        echo "   To: {$event->to}\n";
        echo "   Message: {$event->message}\n";
        
        // Add your custom logic here
        // For example: update database, send notifications, etc.
        
    } elseif ($eventType === WebhookEventType::MESSAGE_RECEIVED) {
        $event = new MessageReceivedEvent($payload);
        echo "📨 Message received event:\n";
        echo "   Campaign: {$event->campaign->title} (ID: {$event->campaign->id})\n";
        echo "   From: {$event->from}\n";
        echo "   To: {$event->to}\n";
        echo "   Message: {$event->message}\n";
        
        // Add your custom logic here
        // For example: auto-reply, update CRM, etc.
    }
    
    return ['received' => true];
}

// 2. Using the built-in webhook handler
$handlers = [
    'onMessageSent' => function($event) {
        echo "🚀 Handler: Message sent to {$event->to}\n";
        echo "   Campaign: {$event->campaign->title}\n";
        echo "   Content: {$event->message}\n";
    },
    'onMessageReceived' => function($event) {
        echo "📥 Handler: Message received from {$event->from}\n";
        echo "   Campaign: {$event->campaign->title}\n";
        echo "   Content: {$event->message}\n";
    }
];

$webhookHandler = Webhook::createHandler($handlers);

// Test with sample payloads
echo "=== Testing Webhook Handler ===\n\n";

// Test message sent event
$messageSentPayload = [
    'type' => WebhookEventType::MESSAGE_SENT,
    'campaign' => [
        'id' => 12345,
        'title' => 'Welcome Campaign',
        'message' => 'Welcome to our service!',
        'senderPhone' => '+15551234567',
        'createdAt' => '2025-01-17T10:00:00Z',
        'runAt' => '2025-01-17T10:00:00Z'
    ],
    'from' => '+15551234567',
    'to' => '+14156961732',
    'message' => 'Hello Andreas, welcome to our service!'
];

echo "1. Testing MESSAGE_SENT event:\n";
handleWebhookPayload($messageSentPayload);
echo "\n";

echo "2. Testing with built-in handler:\n";
$result = $webhookHandler($messageSentPayload);
var_dump($result);
echo "\n";

// Test message received event
$messageReceivedPayload = [
    'type' => WebhookEventType::MESSAGE_RECEIVED,
    'campaign' => [
        'id' => 12345,
        'title' => 'Welcome Campaign',
        'message' => 'Welcome to our service!',
        'senderPhone' => '+15551234567',
        'createdAt' => '2025-01-17T10:00:00Z',
        'runAt' => '2025-01-17T10:00:00Z'
    ],
    'from' => '+14156961732',
    'to' => '+15551234567',
    'message' => 'Thank you! This looks great.'
];

echo "3. Testing MESSAGE_RECEIVED event:\n";
handleWebhookPayload($messageReceivedPayload);
echo "\n";

echo "4. Testing with built-in handler:\n";
$result = $webhookHandler($messageReceivedPayload);
var_dump($result);

echo "\n=== Webhook Handler Examples ===\n\n";

// Example for different PHP frameworks:

echo "// Example 1: Plain PHP webhook endpoint (webhook.php)\n";
echo '<?php
require "vendor/autoload.php";

use CloudContactAI\CCAI\Webhook;

$handlers = [
    "onMessageSent" => function($event) {
        // Log or process sent message
        error_log("Message sent: " . $event->message);
    },
    "onMessageReceived" => function($event) {
        // Process received message
        error_log("Reply received: " . $event->message);
    }
];

$webhookHandler = Webhook::createHandler($handlers);

// Get the raw POST data
$payload = json_decode(file_get_contents("php://input"), true);

// Process the webhook
$result = $webhookHandler($payload);

// Return JSON response
header("Content-Type: application/json");
echo json_encode($result);
';

echo "\n\n// Example 2: Laravel webhook route\n";
echo '// In routes/api.php
Route::post("/ccai-webhook", function (Request $request) {
    $handlers = [
        "onMessageSent" => function($event) {
            Log::info("Message sent", ["to" => $event->to, "message" => $event->message]);
        },
        "onMessageReceived" => function($event) {
            Log::info("Message received", ["from" => $event->from, "message" => $event->message]);
        }
    ];
    
    $webhookHandler = Webhook::createHandler($handlers);
    $result = $webhookHandler($request->all());
    
    return response()->json($result);
});
';

echo "\n\n// Example 3: Symfony webhook controller\n";
echo '// In your controller
public function webhook(Request $request): JsonResponse
{
    $handlers = [
        "onMessageSent" => function($event) {
            $this->logger->info("Message sent", ["event" => $event]);
        },
        "onMessageReceived" => function($event) {
            $this->logger->info("Message received", ["event" => $event]);
        }
    ];
    
    $webhookHandler = Webhook::createHandler($handlers);
    $payload = json_decode($request->getContent(), true);
    $result = $webhookHandler($payload);
    
    return new JsonResponse($result);
}
';

echo "\n\nWebhook handler examples completed!\n";
echo "Note: The webhook management API (/webhooks endpoint) is not available yet,\n";
echo "but the webhook handler functionality works perfectly for processing incoming webhooks.\n";