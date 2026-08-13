<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

// Initialize the client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Example webhook handler for different PHP frameworks.
// The SDK does not provide a handler-builder helper — parse the payload
// with parseWebhookEvent() and dispatch on eventType yourself.

/**
 * @param array $event Parsed event from $ccai->webhook->parseWebhookEvent($rawBody)
 */
function handleWebhookEvent(array $event): array
{
    echo "Received webhook event:\n";

    $data = $event['data'] ?? [];

    switch ($event['eventType'] ?? '') {
        case 'message.sent':
            echo "✅ Message sent event:\n";
            echo "   Campaign: {$data['CampaignTitle']} (ID: {$data['CampaignId']})\n";
            echo "   To: {$data['To']}\n";
            echo "   Message: {$data['Message']}\n";

            // Add your custom logic here
            // For example: update database, send notifications, etc.
            break;

        case 'message.incoming':
        case 'message.received':
            echo "📨 Message received event:\n";
            echo "   Campaign: {$data['CampaignTitle']} (ID: {$data['CampaignId']})\n";
            echo "   From: {$data['From']}\n";
            echo "   Message: {$data['Message']}\n";

            // Add your custom logic here
            // For example: auto-reply, update CRM, etc.
            break;

        default:
            echo "Unknown event type: {$event['eventType']}\n";
    }

    return ['received' => true];
}

// Test with sample payloads
echo "=== Testing Webhook Handler ===\n\n";

$messageSentPayload = json_encode([
    'eventType' => 'message.sent',
    'eventHash' => 'abc123def456ghi789',
    'data' => [
        'To' => '+14156961732',
        'From' => '+15551234567',
        'Message' => 'Hello Andreas, welcome to our service!',
        'CampaignId' => '12345',
        'CampaignTitle' => 'Welcome Campaign',
    ],
]);

echo "1. Testing message.sent event:\n";
$event = $ccai->webhook->parseWebhookEvent($messageSentPayload);
$result = handleWebhookEvent($event);
var_dump($result);
echo "\n";

$messageReceivedPayload = json_encode([
    'eventType' => 'message.incoming',
    'eventHash' => 'xyz789abc123def456',
    'data' => [
        'To' => '+15551234567',
        'From' => '+14156961732',
        'Message' => 'Thank you! This looks great.',
        'CampaignId' => '12345',
        'CampaignTitle' => 'Welcome Campaign',
    ],
]);

echo "2. Testing message.incoming event:\n";
$event = $ccai->webhook->parseWebhookEvent($messageReceivedPayload);
$result = handleWebhookEvent($event);
var_dump($result);

echo "\n=== Webhook Handler Examples ===\n\n";

// Example for different PHP frameworks:

echo "// Example 1: Plain PHP webhook endpoint (webhook.php)\n";
echo '<?php
require "vendor/autoload.php";

use CloudContactAI\CCAI\CCAI;

$ccai = new CCAI([
    "clientId" => getenv("CCAI_CLIENT_ID"),
    "apiKey" => getenv("CCAI_API_KEY"),
]);

// Get the raw POST body and verify the signature
$body = file_get_contents("php://input");
$signature = $_SERVER["HTTP_X_CCAI_SIGNATURE"] ?? "";
$event = $ccai->webhook->parseWebhookEvent($body);

if (!$ccai->webhook->verifySignature($signature, $ccai->getClientId(), $event["eventHash"], "your-webhook-secret")) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid signature"]);
    exit;
}

switch ($event["eventType"]) {
    case "message.sent":
        error_log("Message sent: " . $event["data"]["Message"]);
        break;
    case "message.incoming":
        error_log("Reply received: " . $event["data"]["Message"]);
        break;
}

header("Content-Type: application/json");
echo json_encode(["received" => true]);
';

echo "\n\n// Example 2: Laravel webhook route\n";
echo '// In routes/api.php
Route::post("/ccai-webhook", function (Request $request) use ($ccai) {
    $event = $ccai->webhook->parseWebhookEvent($request->getContent());

    switch ($event["eventType"]) {
        case "message.sent":
            Log::info("Message sent", ["to" => $event["data"]["To"], "message" => $event["data"]["Message"]]);
            break;
        case "message.incoming":
            Log::info("Message received", ["from" => $event["data"]["From"], "message" => $event["data"]["Message"]]);
            break;
    }

    return response()->json(["received" => true]);
});
';

echo "\n\nWebhook handler examples completed!\n";
