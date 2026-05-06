<?php

/**
 * Campaign.php - Campaign registration service for the CloudContactAI API
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Campaigns;

use CloudContactAI\CCAI\CCAI;
use RuntimeException;

/**
 * Campaign service for managing 10DLC campaign registrations
 */
class Campaign
{
    private CCAI $ccai;

    private const USE_CASES = [
        'TWO_FACTOR_AUTHENTICATION', 'ACCOUNT_NOTIFICATION', 'CUSTOMER_CARE',
        'DELIVERY_NOTIFICATION', 'FRAUD_ALERT', 'HIGHER_EDUCATION', 'LOW_VOLUME_MIXED',
        'MARKETING', 'MIXED', 'POLLING_VOTING', 'PUBLIC_SERVICE_ANNOUNCEMENT', 'SECURITY_ALERT',
    ];

    private const SUB_USE_CASES = [
        'TWO_FACTOR_AUTHENTICATION', 'ACCOUNT_NOTIFICATION', 'CUSTOMER_CARE',
        'DELIVERY_NOTIFICATION', 'FRAUD_ALERT', 'MARKETING', 'POLLING_VOTING',
    ];

    private const MIXED_USE_CASES = ['MIXED', 'LOW_VOLUME_MIXED'];

    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Create a new campaign registration
     *
     * @param array $data Campaign data
     * @return array Created campaign response
     * @throws RuntimeException If validation fails or API returns an error
     */
    public function create(array $data): array
    {
        $this->validate($data, true);
        return $this->ccai->complianceRequest('POST', '/v1/campaigns', $data);
    }

    /**
     * Get a campaign by ID
     *
     * @param int $id Campaign ID
     * @return array Campaign response
     * @throws RuntimeException If the API returns an error
     */
    public function get(int $id): array
    {
        return $this->ccai->complianceRequest('GET', "/v1/campaigns/{$id}");
    }

    /**
     * List all campaigns for the account
     *
     * @return array List of campaign responses
     * @throws RuntimeException If the API returns an error
     */
    public function list(): array
    {
        return $this->ccai->complianceRequest('GET', '/v1/campaigns');
    }

    /**
     * Update an existing campaign
     *
     * @param int   $id   Campaign ID
     * @param array $data Updated campaign data
     * @return array Updated campaign response
     * @throws RuntimeException If validation fails or API returns an error
     */
    public function update(int $id, array $data): array
    {
        $this->validate($data, false);
        return $this->ccai->complianceRequest('PATCH', "/v1/campaigns/{$id}", $data);
    }

    /**
     * Delete a campaign by ID
     *
     * @param int $id Campaign ID
     * @throws RuntimeException If the API returns an error
     */
    public function delete(int $id): void
    {
        $this->ccai->complianceRequest('DELETE', "/v1/campaigns/{$id}");
    }

    /**
     * Validate campaign data before sending to the API
     *
     * @param array $data     Campaign data
     * @param bool  $isCreate Whether this is a create operation (stricter validation)
     * @throws RuntimeException If validation fails
     */
    private function validate(array $data, bool $isCreate): void
    {
        $errors = [];

        if ($isCreate) {
            if (empty($data['brandId'])) {
                $errors[] = 'brandId is required';
            }
            if (empty($data['useCase'])) {
                $errors[] = 'useCase is required';
            }
            if (empty($data['description'])) {
                $errors[] = 'description is required';
            }
            if (empty($data['messageFlow'])) {
                $errors[] = 'messageFlow is required';
            }
            if (!isset($data['hasEmbeddedLinks'])) {
                $errors[] = 'hasEmbeddedLinks is required';
            }
            if (!isset($data['hasEmbeddedPhone'])) {
                $errors[] = 'hasEmbeddedPhone is required';
            }
            if (!isset($data['isAgeGated'])) {
                $errors[] = 'isAgeGated is required';
            }
            if (!isset($data['isDirectLending'])) {
                $errors[] = 'isDirectLending is required';
            }
            if (empty($data['optInKeywords'])) {
                $errors[] = 'optInKeywords is required';
            }
            if (empty($data['optInMessage'])) {
                $errors[] = 'optInMessage is required';
            }
            if (empty($data['optInProofUrl'])) {
                $errors[] = 'optInProofUrl is required';
            }
            if (empty($data['helpKeywords'])) {
                $errors[] = 'helpKeywords is required';
            }
            if (empty($data['helpMessage'])) {
                $errors[] = 'helpMessage is required';
            }
            if (empty($data['optOutKeywords'])) {
                $errors[] = 'optOutKeywords is required';
            }
            if (empty($data['optOutMessage'])) {
                $errors[] = 'optOutMessage is required';
            }
            if (empty($data['sampleMessages'])) {
                $errors[] = 'sampleMessages is required';
            }
        }

        if (!empty($data['useCase']) && !in_array($data['useCase'], self::USE_CASES, true)) {
            $errors[] = 'useCase must be one of: ' . implode(', ', self::USE_CASES);
        }

        if (!empty($data['useCase']) && in_array($data['useCase'], self::MIXED_USE_CASES, true)) {
            if (empty($data['subUseCases']) || !is_array($data['subUseCases'])) {
                $errors[] = 'subUseCases is required for MIXED/LOW_VOLUME_MIXED use cases';
            } elseif (count($data['subUseCases']) < 2 || count($data['subUseCases']) > 3) {
                $errors[] = 'subUseCases must have 2-3 items for MIXED/LOW_VOLUME_MIXED use cases';
            } else {
                foreach ($data['subUseCases'] as $subUseCase) {
                    if (!in_array($subUseCase, self::SUB_USE_CASES, true)) {
                        $errors[] = "subUseCase '{$subUseCase}' is invalid";
                    }
                }
            }
        }

        if (!empty($data['sampleMessages'])) {
            $sampleMessages = $data['sampleMessages'];
            if (!is_array($sampleMessages) || count($sampleMessages) < 2 || count($sampleMessages) > 5) {
                $errors[] = 'sampleMessages must have 2-5 items';
            } else {
                $optOutKeywords = $data['optOutKeywords'] ?? [];
                $helpKeywords   = $data['helpKeywords'] ?? [];

                $hasStop = false;
                $hasHelp = false;
                foreach ($sampleMessages as $msg) {
                    if (stripos($msg, 'Reply STOP') !== false || $this->containsKeyword($msg, $optOutKeywords)) {
                        $hasStop = true;
                    }
                    if (stripos($msg, 'Reply HELP') !== false || $this->containsKeyword($msg, $helpKeywords)) {
                        $hasHelp = true;
                    }
                }
                if (!$hasStop) {
                    $errors[] = 'At least one sampleMessage must contain "Reply STOP" or an optOutKeyword';
                }
                if (!$hasHelp) {
                    $errors[] = 'At least one sampleMessage must contain "Reply HELP" or a helpKeyword';
                }
            }
        }

        if (!empty($data['optOutMessage'])) {
            $optOutKeywords = $data['optOutKeywords'] ?? [];
            if (stripos($data['optOutMessage'], 'STOP') === false && !$this->containsKeyword($data['optOutMessage'], $optOutKeywords)) {
                $errors[] = 'optOutMessage must contain "STOP" or an optOutKeyword';
            }
        }

        if (!empty($data['helpMessage'])) {
            $helpKeywords = $data['helpKeywords'] ?? [];
            if (stripos($data['helpMessage'], 'HELP') === false && !$this->containsKeyword($data['helpMessage'], $helpKeywords)) {
                $errors[] = 'helpMessage must contain "HELP" or a helpKeyword';
            }
        }

        foreach (['optInProofUrl', 'termsLink', 'privacyLink'] as $urlField) {
            if (!empty($data[$urlField]) && !preg_match('#^https?://#', $data[$urlField])) {
                $errors[] = "{$urlField} must start with http:// or https://";
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException('Campaign validation failed: ' . implode(', ', $errors));
        }
    }

    /**
     * Check if a string contains any keyword from the given list
     *
     * @param string   $text     Text to search in
     * @param string[] $keywords Keywords to look for
     * @return bool True if any keyword is found
     */
    private function containsKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}
