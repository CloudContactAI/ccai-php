<?php

/**
 * MMS example using the CCAI PHP client
 *
 * @license MIT
 * @copyright 2025 CloudContactAI LLC
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\SMSOptions;

// Create a new CCAI client
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Path to the image file you want to send
$filename = 'AllCode.png';
$imagePath = __DIR__ . '/' .$filename;
$contentType = 'image/png';

// Create an account for the recipient
$account = new Account(
    'Jane',
    'Doe',
    '+15555555555'
);

// Message content (can include ${firstName} and ${lastName} variables)
$message = 'Hi ${firstName} ${lastName}, testing a new campaign';
$title = 'MMS Content Test Message';

// Create options with a progress callback
$options = new SMSOptions(
    60,  // timeout
    null, // retries
    function ($status) {
        echo date('Y-m-d H:i:s') . " - $status\n";
    }
);

try {
    echo "Starting MMS workflow...\n";
    
    // Step 1: Get a signed URL for uploading the image
    echo "Step 1: Getting signed upload URL...\n";
    $uploadResponse = $ccai->mms->getSignedUploadUrl(
        basename($imagePath),
        $contentType,
        null,  // Use default fileBasePath
        true   // Explicitly set publicFile to true
    );
    
    $signedUrl = $uploadResponse['signedS3Url'];
    $fileKey = $uploadResponse['fileKey'];

    echo "Got signed URL: " . substr($signedUrl, 0, 50) . "...\n";
    echo "File key: $fileKey\n";

    // Step 2: Upload the image to the signed URL
    echo "Step 2: Uploading image to S3...\n";
    $uploadSuccess = $ccai->mms->uploadImageToSignedUrl(
        $signedUrl,
        $imagePath,
        $contentType
    );
    
    if (!$uploadSuccess) {
        throw new RuntimeException("Failed to upload image to S3");
    }
    
    echo "Upload successful!\n";
    
    // Step 3: Send the MMS with the uploaded image
    echo "Step 3: Sending MMS campaign...\n";
    $response = $ccai->mms->send(
        $fileKey,
        [$account],
        $message,
        $title,
        $options
    );
    
    echo "MMS sent successfully!\n";
    echo "Campaign ID: " . $response->campaignId . "\n";
    echo "Messages sent: " . $response->messagesSent . "\n";
    echo "Status: " . $response->status . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
