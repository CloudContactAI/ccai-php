<?php

/**
 * Webhook.php - A PHP module for managing CloudContactAI webhooks
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Webhook;

use CloudContactAI\CCAI\CCAI;
use RuntimeException;

/**
 * Webhook service for registering and managing CCAI webhook endpoints
 */
class Webhook
{
    private CCAI $ccai;

    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Register a new webhook endpoint
     *
     * If config['secretKey'] is not provided, the server will auto-generate one
     *
     * @param array $config Webhook configuration (url, events, secret, etc.)
     * @return array Registered webhook details
     */
    public function register(array $config): array
    {
        $clientId = $this->ccai->getClientId();
        $payload = [[
            'url'             => $config['url'] ?? '',
            'method'          => $config['method'] ?? 'POST',
            'integrationType' => $config['integrationType'] ?? 'ALL',
        ]];

        // Only include secretKey if explicitly provided
        if (isset($config['secretKey'])) {
            $payload[0]['secretKey'] = $config['secretKey'];
        }

        $result = $this->ccai->request('POST', "/v1/client/{$clientId}/integration", $payload);
        // API returns an array of created webhooks — return the first one
        return is_array($result) && isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Update an existing webhook configuration
     *
     * If config['secretKey'] is not provided, the server will keep the existing secret
     *
     * @param string $id Webhook ID
     * @param array $config Updated webhook configuration
     * @return array Updated webhook details
     */
    public function update(string $id, array $config): array
    {
        $clientId = $this->ccai->getClientId();
        $payload = [[
            'id'              => (int) $id,
            'url'             => $config['url'] ?? '',
            'method'          => $config['method'] ?? 'POST',
            'integrationType' => $config['integrationType'] ?? 'ALL',
        ]];

        // Only include secretKey if explicitly provided
        if (isset($config['secretKey'])) {
            $payload[0]['secretKey'] = $config['secretKey'];
        }

        $result = $this->ccai->request('POST', "/v1/client/{$clientId}/integration", $payload);
        return is_array($result) && isset($result[0]) ? $result[0] : $result;
    }

    /**
     * List all registered webhooks
     *
     * @return array Array of webhook configurations
     */
    public function list(): array
    {
        $clientId = $this->ccai->getClientId();
        return $this->ccai->request('GET', "/v1/client/{$clientId}/integration");
    }

    /**
     * Delete a webhook
     *
     * @param string $id Webhook ID
     * @return array Success response
     */
    public function delete(string $id): array
    {
        $clientId = $this->ccai->getClientId();
        return $this->ccai->request('DELETE', "/v1/client/{$clientId}/integration/{$id}");
    }

    /**
     * Verify a webhook signature using HMAC-SHA256
     *
     * Signature is computed as: HMAC-SHA256(secretKey, clientId:eventHash) encoded in Base64
     *
     * @param string $signature Signature from the X-CCAI-Signature header (Base64 encoded)
     * @param string $clientId Client ID
     * @param string $eventHash Event hash from the webhook payload
     * @param string $secret Webhook secret
     * @return bool True if the signature is valid
     */
    public function verifySignature(string $signature, string $clientId, string $eventHash, string $secret): bool
    {
        // Compute: HMAC-SHA256(secretKey, "$clientId:$eventHash")
        $data = "{$clientId}:{$eventHash}";
        $computed = hash_hmac('sha256', $data, $secret, true); // raw binary output
        $computedBase64 = base64_encode($computed);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($computedBase64, $signature);
    }

    /**
     * Parse a raw webhook payload into a structured event array
     *
     * @param string $payload Raw JSON payload
     * @return array Parsed webhook event
     * @throws RuntimeException If the payload is invalid JSON
     */
    public function parseWebhookEvent(string $payload): array
    {
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON payload: ' . json_last_error_msg());
        }
        return $data;
    }
}
