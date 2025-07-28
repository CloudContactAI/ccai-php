<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\WebhookEventType;
use CloudContactAI\CCAI\Webhook;

// Simple PHP webhook server
echo "🚀 Starting CCAI Webhook Server on http://localhost:8080/webhook\n";
echo "📱 Waiting for webhook events...\n\n";

// Create webhook handlers
$handlers = [
    'onMessageSent' => function($event) {
        echo "✅ MESSAGE SENT:\n";
        echo "   Campaign: {$event->campaign->title} (ID: {$event->campaign->id})\n";
        echo "   From: {$event->from}\n";
        echo "   To: {$event->to}\n";
        echo "   Message: {$event->message}\n";
        echo "   Time: " . date('Y-m-d H:i:s') . "\n\n";
    },
    'onMessageReceived' => function($event) {
        echo "📨 MESSAGE RECEIVED:\n";
        echo "   Campaign: {$event->campaign->title} (ID: {$event->campaign->id})\n";
        echo "   From: {$event->from}\n";
        echo "   To: {$event->to}\n";
        echo "   Message: {$event->message}\n";
        echo "   Time: " . date('Y-m-d H:i:s') . "\n\n";
    }
];

$webhookHandler = Webhook::createHandler($handlers);

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
            $payload = json_decode($body, true);
            
            if ($payload) {
                echo "🔔 Webhook received at " . date('Y-m-d H:i:s') . "\n";
                
                // Process the webhook
                $result = $webhookHandler($payload);
                
                // Send HTTP response
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: application/json\r\n";
                $response .= "Access-Control-Allow-Origin: *\r\n";
                $response .= "\r\n";
                $response .= json_encode($result);
                
                socket_write($client, $response);
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