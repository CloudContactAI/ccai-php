<?php

/**
 * Brand.php - Brand registration service for the CloudContactAI API
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\Brands;

use CloudContactAI\CCAI\CCAI;
use RuntimeException;

/**
 * Brand service for managing brand registrations (10DLC)
 */
class Brand
{
    private CCAI $ccai;

    private const ENTITY_TYPES = [
        'PRIVATE_PROFIT', 'PUBLIC_PROFIT', 'NON_PROFIT', 'GOVERNMENT', 'SOLE_PROPRIETOR',
    ];

    private const VERTICAL_TYPES = [
        'AUTOMOTIVE', 'AGRICULTURE', 'BANKING', 'COMMUNICATION', 'CONSTRUCTION',
        'EDUCATION', 'ENERGY', 'ENTERTAINMENT', 'GOVERNMENT', 'HEALTHCARE',
        'HOSPITALITY', 'INSURANCE', 'LEGAL', 'MANUFACTURING', 'NON_PROFIT',
        'PROFESSIONAL', 'REAL_ESTATE', 'RETAIL', 'TECHNOLOGY', 'TRANSPORTATION',
    ];

    private const TAX_ID_COUNTRIES = ['US', 'CA', 'GB', 'AU'];

    private const STOCK_EXCHANGES = ['NASDAQ', 'NYSE', 'AMEX', 'TSX', 'LON', 'JPX', 'HKEX', 'OTHER'];

    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
    }

    /**
     * Create a new brand registration
     *
     * @param array $data Brand data
     * @return array Created brand response
     * @throws RuntimeException If validation fails or API returns an error
     */
    public function create(array $data): array
    {
        $this->validate($data, true);
        return $this->ccai->complianceRequest('POST', '/v1/brands', $data);
    }

    /**
     * Get a brand by ID
     *
     * @param int $id Brand ID
     * @return array Brand response
     * @throws RuntimeException If the API returns an error
     */
    public function get(int $id): array
    {
        return $this->ccai->complianceRequest('GET', "/v1/brands/{$id}");
    }

    /**
     * List all brands for the account
     *
     * @return array List of brand responses
     * @throws RuntimeException If the API returns an error
     */
    public function list(): array
    {
        return $this->ccai->complianceRequest('GET', '/v1/brands');
    }

    /**
     * Update an existing brand
     *
     * @param int   $id   Brand ID
     * @param array $data Updated brand data
     * @return array Updated brand response
     * @throws RuntimeException If validation fails or API returns an error
     */
    public function update(int $id, array $data): array
    {
        $this->validate($data, false);
        return $this->ccai->complianceRequest('PATCH', "/v1/brands/{$id}", $data);
    }

    /**
     * Delete a brand by ID
     *
     * @param int $id Brand ID
     * @throws RuntimeException If the API returns an error
     */
    public function delete(int $id): void
    {
        $this->ccai->complianceRequest('DELETE', "/v1/brands/{$id}");
    }

    /**
     * Validate brand data before sending to the API
     *
     * @param array $data     Brand data
     * @param bool  $isCreate Whether this is a create operation (stricter validation)
     * @throws RuntimeException If validation fails
     */
    private function validate(array $data, bool $isCreate): void
    {
        $errors = [];

        if ($isCreate) {
            if (empty($data['legalCompanyName'])) {
                $errors[] = 'legalCompanyName is required';
            }
            if (empty($data['entityType'])) {
                $errors[] = 'entityType is required';
            }
            if (empty($data['taxId'])) {
                $errors[] = 'taxId is required';
            }
            if (empty($data['taxIdCountry'])) {
                $errors[] = 'taxIdCountry is required';
            }
            if (empty($data['country'])) {
                $errors[] = 'country is required';
            }
            if (empty($data['verticalType'])) {
                $errors[] = 'verticalType is required';
            }
            if (empty($data['websiteUrl'])) {
                $errors[] = 'websiteUrl is required';
            }
            if (empty($data['street'])) {
                $errors[] = 'street is required';
            }
            if (empty($data['city'])) {
                $errors[] = 'city is required';
            }
            if (empty($data['state'])) {
                $errors[] = 'state is required';
            }
            if (empty($data['postalCode'])) {
                $errors[] = 'postalCode is required';
            }
            if (empty($data['contactFirstName'])) {
                $errors[] = 'contactFirstName is required';
            }
            if (empty($data['contactLastName'])) {
                $errors[] = 'contactLastName is required';
            }
            if (empty($data['contactEmail'])) {
                $errors[] = 'contactEmail is required';
            }
            if (empty($data['contactPhone'])) {
                $errors[] = 'contactPhone is required';
            }
        }

        if (!empty($data['entityType']) && !in_array($data['entityType'], self::ENTITY_TYPES, true)) {
            $errors[] = 'entityType must be one of: ' . implode(', ', self::ENTITY_TYPES);
        }

        if (!empty($data['verticalType']) && !in_array($data['verticalType'], self::VERTICAL_TYPES, true)) {
            $errors[] = 'verticalType must be one of: ' . implode(', ', self::VERTICAL_TYPES);
        }

        if (!empty($data['taxIdCountry']) && !in_array($data['taxIdCountry'], self::TAX_ID_COUNTRIES, true)) {
            $errors[] = 'taxIdCountry must be one of: ' . implode(', ', self::TAX_ID_COUNTRIES);
        }

        if (!empty($data['stockExchange']) && !in_array($data['stockExchange'], self::STOCK_EXCHANGES, true)) {
            $errors[] = 'stockExchange must be one of: ' . implode(', ', self::STOCK_EXCHANGES);
        }

        if (!empty($data['websiteUrl']) && !preg_match('#^https?://#', $data['websiteUrl'])) {
            $errors[] = 'websiteUrl must start with http:// or https://';
        }

        if (!empty($data['contactEmail']) && !filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'contactEmail must be a valid email address';
        }

        if (!empty($data['taxId']) && !empty($data['taxIdCountry'])
            && in_array($data['taxIdCountry'], ['US', 'CA'], true)
            && !preg_match('/^\d{9}$/', $data['taxId'])
        ) {
            $errors[] = 'taxId must be exactly 9 digits for US/CA';
        }

        if (!empty($data['entityType']) && $data['entityType'] === 'PUBLIC_PROFIT') {
            if (empty($data['stockSymbol'])) {
                $errors[] = 'stockSymbol is required for PUBLIC_PROFIT entities';
            }
            if (empty($data['stockExchange'])) {
                $errors[] = 'stockExchange is required for PUBLIC_PROFIT entities';
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException('Brand validation failed: ' . implode(', ', $errors));
        }
    }
}
