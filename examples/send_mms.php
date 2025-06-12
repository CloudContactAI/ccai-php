<?php

/**
 * Example of sending an MMS message using the CCAI PHP library
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\SMSOptions;

// Replace with your actual credentials
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Path to the image file you want to send
$filename = 'AllCode.png';
$imagePath = __DIR__ . '/AllCode.png';
$contentType = 'image/png';

// Create an account for the recipient
$account = new Account('John', 'Doe', '+15555555555');

// Message content (can include ${firstName} and ${lastName} variables)
$message = 'Hi ${firstName} ${lastName}, check out this image!';
$title = 'MMS Test Campaign';

// Optional: Create options with a progress callback
$options = new SMSOptions(
    timeout: 60,
    onProgress: function ($status) {
        echo "Status: $status\n";
    }
);

try {
    // Method 1: Complete workflow in one step
    $response = $ccai->mms->sendWithImage(
        $imagePath,
        $contentType,
        [$account],
        $message,
        $title,
        $options
    );
    
    echo "MMS sent successfully! Campaign ID: " . $response->campaignId . "\n";
    
    // Method 2: Step by step approach
    // Step 1: Get a signed URL for uploading the image
    $uploadResponse = $ccai->mms->getSignedUploadUrl(
        basename($imagePath),
        $contentType
    );
    
    $signedUrl = $uploadResponse['signedS3Url'];
    $fileKey = $uploadResponse['fileKey'];
    
    echo "Got signed URL: $signedUrl\n";
    echo "File key: $fileKey\n";
    
    // Step 2: Upload the image to the signed URL
    $uploadSuccess = $ccai->mms->uploadImageToSignedUrl(
        $signedUrl,
        $imagePath,
        $contentType
    );
    
    echo "Upload successful: " . ($uploadSuccess ? 'Yes' : 'No') . "\n";
    
    // Step 3: Send the MMS with the uploaded image
    $response = $ccai->mms->send(
        $fileKey,
        [$account],
        $message,
        $title,
        $options
    );
    
    echo "MMS sent successfully! Campaign ID: " . $response->campaignId . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
