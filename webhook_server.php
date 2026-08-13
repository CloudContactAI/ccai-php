<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

// Initialize the client (only needed here to parse events; no credentials required for that)
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Simple PHP webhook server
echo "🚀 Starting CCAI Webhook Server on http://localhost:8080/webhook\n";
echo "📱 Waiting for webhook events...\n\n";

function handleWebhookEvent(array $event): void
{
    $data = $event['data'] ?? [];

    switch ($event['eventType'] ?? '') {
        case 'message.sent':
            echo "✅ MESSAGE SENT:\n";
            echo "   Campaign: {$data['CampaignTitle']} (ID: {$data['CampaignId']})\n";
            echo "   To: {$data['To']}\n";
            echo "   Message: {$data['Message']}\n";
            echo "   Time: " . date('Y-m-d H:i:s') . "\n\n";
            break;

        case 'message.incoming':
        case 'message.received':
            echo "📨 MESSAGE RECEIVED:\n";
            echo "   Campaign: {$data['CampaignTitle']} (ID: {$data['CampaignId']})\n";
            echo "   From: {$data['From']}\n";
            echo "   Message: {$data['Message']}\n";
            echo "   Time: " . date('Y-m-d H:i:s') . "\n\n";
            break;

        default:
            echo "ℹ️ Unhandled event type: {$event['eventType']}\n\n";
    }
}

// Start simple HTTP server
$host = 'localhost';
$port = 8080;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, $host, $port);
socket_listen($socket);

while (true) {
    $client = socket_accept($socket);

    // Read the HTTP request
    $request = socket_read($client, 2048);

    // Parse the request
    $lines = explode("\n", $request);
    $firstLine = $lines[0];

    if (strpos($firstLine, 'POST /webhook') !== false) {
        // Find the JSON payload in the request body
        $bodyStart = strpos($request, "\r\n\r\n");
        if ($bodyStart !== false) {
            $body = substr($request, $bodyStart + 4);

            try {
                $event = $ccai->webhook->parseWebhookEvent($body);
                echo "🔔 Webhook received at " . date('Y-m-d H:i:s') . "\n";
                handleWebhookEvent($event);

                // Send HTTP response
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: application/json\r\n";
                $response .= "Access-Control-Allow-Origin: *\r\n";
                $response .= "\r\n";
                $response .= json_encode(['received' => true]);

                socket_write($client, $response);
            } catch (\Exception $e) {
                echo "❌ Failed to parse webhook payload: {$e->getMessage()}\n";
            }
        }
    } else {
        // Send a simple response for other requests
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/html\r\n";
        $response .= "\r\n";
        $response .= "<h1>CCAI Webhook Server</h1>";
        $response .= "<p>Webhook endpoint: POST /webhook</p>";
        $response .= "<p>Server running at http://localhost:8080</p>";

        socket_write($client, $response);
    }

    socket_close($client);
}

socket_close($socket);
