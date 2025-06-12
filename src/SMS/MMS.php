<?php

/**
 * MMS.php - MMS service for the CCAI API
 * Handles sending MMS messages through the Cloud Contact AI platform.
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

declare(strict_types=1);

namespace CloudContactAI\CCAI\SMS;

use CloudContactAI\CCAI\CCAI;
use GuzzleHttp\Client;
use InvalidArgumentException;
use RuntimeException;

/**
 * MMS service for sending multimedia messages through the CCAI API
 */
class MMS
{
    /**
     * @var CCAI The parent CCAI instance
     */
    private CCAI $ccai;

    /**
     * @var Client HTTP client for file uploads
     */
    private Client $httpClient;

    /**
     * Create a new MMS service instance
     *
     * @param CCAI $ccai The parent CCAI instance
     */
    public function __construct(CCAI $ccai)
    {
        $this->ccai = $ccai;
        $this->httpClient = new Client();
    }

    /**
     * Get a signed S3 URL to upload an image file
     *
     * @param string $fileName Name of the file to upload
     * @param string $fileType MIME type of the file
     * @param string $fileBasePath Base path for the file in S3 (default: clientId/campaign)
     * @param bool $publicFile Whether the file should be public (default: true)
     * 
     * @return array Response containing the signed URL and file key
     * 
     * @throws InvalidArgumentException If required parameters are missing or invalid
     * @throws RuntimeException If the API request fails
     */
    public function getSignedUploadUrl(
        string $fileName,
        string $fileType,
        ?string $fileBasePath = null,
        bool $publicFile = true
    ): array {
        if (empty($fileName)) {
            throw new InvalidArgumentException('File name is required');
        }

        if (empty($fileType)) {
            throw new InvalidArgumentException('File type is required');
        }

        // Use default fileBasePath if not provided
        $fileBasePath = $fileBasePath ?? $this->ccai->getClientId() . '/campaign';
        
        // Define fileKey explicitly as clientId/campaign/filename
        $fileKey = $this->ccai->getClientId() . "/campaign/" . $fileName;

        $data = [
            'fileName' => $fileName,
            'fileType' => $fileType,
            'fileBasePath' => $fileBasePath,
            'publicFile' => $publicFile ? true : false // Ensure boolean is properly encoded in JSON
        ];

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://files.cloudcontactai.com/upload/url',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->ccai->getApiKey(),
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $data
                ]
            );

            $contents = $response->getBody()->getContents();
            $responseData = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $contents), true);
            
            if (!isset($responseData['signedS3Url'])) {
                throw new RuntimeException('Invalid response from upload URL API');
            }
            
            // Override the fileKey with our explicitly defined one
            $responseData['fileKey'] = $fileKey;

            return $responseData;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to get signed upload URL: ' . $e->getMessage());
        }
    }

    /**
     * Upload an image file to a signed S3 URL
     *
     * @param string $signedUrl The signed S3 URL to upload to
     * @param string $filePath Path to the file to upload
     * @param string $contentType MIME type of the file
     * 
     * @return bool True if upload was successful
     * 
     * @throws InvalidArgumentException If required parameters are missing or invalid
     * @throws RuntimeException If the file upload fails
     */
    public function uploadImageToSignedUrl(
        string $signedUrl,
        string $filePath,
        string $contentType
    ): bool {
        if (empty($signedUrl)) {
            throw new InvalidArgumentException('Signed URL is required');
        }

        if (empty($filePath)) {
            throw new InvalidArgumentException('File path is required');
        }

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('File does not exist: ' . $filePath);
        }

        if (empty($contentType)) {
            throw new InvalidArgumentException('Content type is required');
        }

        try {
            $fileContents = file_get_contents($filePath);
            
            if ($fileContents === false) {
                throw new RuntimeException('Failed to read file: ' . $filePath);
            }

            $response = $this->httpClient->request(
                'PUT',
                $signedUrl,
                [
                    'headers' => [
                        'Content-Type' => $contentType
                    ],
                    'body' => $fileContents
                ]
            );

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Send an MMS message to one or more recipients
     *
     * @param string $pictureFileKey S3 file key for the image
     * @param array $accounts Array of Account objects or arrays
     * @param string $message Message content (can include ${firstName} and ${lastName} variables)
     * @param string $title Campaign title
     * @param SMSOptions|null $options Optional settings for the MMS send operation
     * @param bool $forceNewCampaign Whether to force a new campaign (default: true)
     * 
     * @return SMSResponse API response
     * 
     * @throws InvalidArgumentException If required parameters are missing or invalid
     */
    public function send(
        string $pictureFileKey,
        array $accounts,
        string $message,
        string $title,
        ?SMSOptions $options = null,
        bool $forceNewCampaign = true
    ): SMSResponse {
        // Validate inputs
        if (empty($pictureFileKey)) {
            throw new InvalidArgumentException('Picture file key is required');
        }

        if (empty($accounts)) {
            throw new InvalidArgumentException('At least one account is required');
        }

        if (empty($message)) {
            throw new InvalidArgumentException('Message is required');
        }

        if (empty($title)) {
            throw new InvalidArgumentException('Campaign title is required');
        }

        // Create options if not provided
        $options = $options ?? new SMSOptions();

        // Normalize accounts
        $normalizedAccounts = [];
        foreach ($accounts as $index => $account) {
            if ($account instanceof Account) {
                $normalizedAccounts[] = $account;
            } elseif (is_array($account)) {
                try {
                    $normalizedAccounts[] = Account::fromArray($account);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid account at index %d: %s', $index, $e->getMessage())
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    sprintf('Invalid account at index %d: must be an Account object or array', $index)
                );
            }
        }

        // Notify progress if callback provided
        $options->notifyProgress('Preparing to send MMS');

        // Prepare the endpoint and data
        $endpoint = '/clients/' . $this->ccai->getClientId() . '/campaigns/direct';

        // Convert Account objects to arrays for API compatibility
        $accountsData = array_map(
            fn(Account $account) => $account->toArray(),
            $normalizedAccounts
        );

        $campaignData = [
            'pictureFileKey' => $pictureFileKey,
            'accounts' => $accountsData,
            'message' => $message,
            'title' => $title
        ];

        try {
            // Notify progress if callback provided
            $options->notifyProgress('Sending MMS');

            // Set up headers
            $headers = [
                'Authorization' => 'Bearer ' . $this->ccai->getApiKey(),
                'Content-Type' => 'application/json'
            ];

            if ($forceNewCampaign) {
                $headers['ForceNewCampaign'] = 'true';
            }

            // Make the API request
            $response = $this->httpClient->request(
                'POST',
                $this->ccai->getBaseUrl() . $endpoint,
                [
                    'headers' => $headers,
                    'json' => $campaignData,
                    'timeout' => $options->timeout ?? 30
                ]
            );

            $responseData = json_decode($response->getBody()->getContents(), true) ?? [];

            // Notify progress if callback provided
            $options->notifyProgress('MMS sent successfully');

            // Convert response to SMSResponse object
            return new SMSResponse($responseData);
        } catch (\Exception $e) {
            // Notify progress if callback provided
            $options->notifyProgress('MMS sending failed');

            throw new RuntimeException('Failed to send MMS: ' . $e->getMessage());
        }
    }

    /**
     * Send a single MMS message to one recipient
     *
     * @param string $pictureFileKey S3 file key for the image
     * @param string $firstName Recipient's first name
     * @param string $lastName Recipient's last name
     * @param string $phone Recipient's phone number (E.164 format)
     * @param string $message Message content (can include ${firstName} and ${lastName} variables)
     * @param string $title Campaign title
     * @param SMSOptions|null $options Optional settings for the MMS send operation
     * @param bool $forceNewCampaign Whether to force a new campaign (default: true)
     * 
     * @return SMSResponse API response
     */
    public function sendSingle(
        string $pictureFileKey,
        string $firstName,
        string $lastName,
        string $phone,
        string $message,
        string $title,
        ?SMSOptions $options = null,
        bool $forceNewCampaign = true
    ): SMSResponse {
        $account = new Account($firstName, $lastName, $phone);

        return $this->send(
            $pictureFileKey,
            [$account],
            $message,
            $title,
            $options,
            $forceNewCampaign
        );
    }

    /**
     * Complete MMS workflow: get signed URL, upload image, and send MMS
     *
     * @param string $imagePath Path to the image file
     * @param string $contentType MIME type of the image
     * @param array $accounts Array of Account objects or arrays
     * @param string $message Message content (can include ${firstName} and ${lastName} variables)
     * @param string $title Campaign title
     * @param SMSOptions|null $options Optional settings for the MMS send operation
     * @param bool $forceNewCampaign Whether to force a new campaign (default: true)
     * 
     * @return SMSResponse API response
     * 
     * @throws InvalidArgumentException If required parameters are missing or invalid
     * @throws RuntimeException If any step of the process fails
     */
    public function sendWithImage(
        string $imagePath,
        string $contentType,
        array $accounts,
        string $message,
        string $title,
        ?SMSOptions $options = null,
        bool $forceNewCampaign = true
    ): SMSResponse {
        // Create options if not provided
        $options = $options ?? new SMSOptions();

        // Step 1: Get the file name from the path
        $fileName = basename($imagePath);
        
        // Notify progress if callback provided
        $options->notifyProgress('Getting signed upload URL');
        
        // Step 2: Get a signed URL for uploading
        $uploadResponse = $this->getSignedUploadUrl($fileName, $contentType);
        $signedUrl = $uploadResponse['signedS3Url'];
        $fileKey = $uploadResponse['fileKey'];
        
        // Notify progress if callback provided
        $options->notifyProgress('Uploading image to S3');
        
        // Step 3: Upload the image to the signed URL
        $uploadSuccess = $this->uploadImageToSignedUrl($signedUrl, $imagePath, $contentType);
        
        if (!$uploadSuccess) {
            throw new RuntimeException('Failed to upload image to S3');
        }
        
        // Notify progress if callback provided
        $options->notifyProgress('Image uploaded successfully, sending MMS');
        
        // Step 4: Send the MMS with the uploaded image
        return $this->send(
            $fileKey,
            $accounts,
            $message,
            $title,
            $options,
            $forceNewCampaign
        );
    }
}
