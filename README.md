# CCAI PHP Client

A PHP client for interacting with the CloudContactAI API.

## Installation

```bash
composer require cloudcontactai/ccai-php
```

## Requirements

- PHP 8.1 or higher
- Composer
- GuzzleHttp 7.0+

## Configuration

You can configure the client using environment variables:

```bash
# Set your CCAI credentials as environment variables
export CCAI_CLIENT_ID="your-client-id"
export CCAI_API_KEY="your-api-key"
```

Or provide them directly in your code:

```php
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);
```

## Usage

### Sending SMS Messages

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;

// Initialize the client
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Send a single SMS
$response = $ccai->sms->sendSingle(
    firstName: 'John',
    lastName: 'Doe',
    phone: '+15551234567',
    message: 'Hello ${firstName}, this is a test message!',
    title: 'Test Campaign'
);

echo "Message sent with ID: " . $response->id . "\n";

// Send to multiple recipients
$accounts = [
    new Account('John', 'Doe', '+15551234567'),
    new Account('Jane', 'Smith', '+15559876543')
];

$campaignResponse = $ccai->sms->send(
    accounts: $accounts,
    message: 'Hello ${firstName} ${lastName}, this is a test message!',
    title: 'Bulk Test Campaign'
);

echo "Campaign sent with ID: " . $campaignResponse->campaignId . "\n";
```

### Sending MMS Messages

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\SMS\SMSOptions;

// Initialize the client
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Method 1: All-in-one approach - Upload image and send MMS in one step
$imagePath = '/path/to/your/image.png';
$contentType = 'image/png';

// Create an account for the recipient
$account = new Account('John', 'Doe', '+15551234567');

// Optional: Create options with a progress callback
$options = new SMSOptions(
    timeout: 60,
    onProgress: function ($status) {
        echo "Status: $status\n";
    }
);

// Send MMS with image in one step
$response = $ccai->mms->sendWithImage(
    $imagePath,
    $contentType,
    [$account],
    'Hi ${firstName}, check out this image!',
    'MMS Campaign'
);

echo "MMS sent successfully! Campaign ID: " . $response->campaignId . "\n";

// Method 2: Step-by-step approach
// Step 1: Get a signed URL for uploading the image
$uploadResponse = $ccai->mms->getSignedUploadUrl(
    'image.png',
    'image/png'
);

$signedUrl = $uploadResponse['url'];
$fileKey = $uploadResponse['fileKey'];

// Step 2: Upload the image to the signed URL
$uploadSuccess = $ccai->mms->uploadImageToSignedUrl(
    $signedUrl,
    $imagePath,
    'image/png'
);

// Step 3: Send the MMS with the uploaded image
$response = $ccai->mms->send(
    $fileKey,
    [$account],
    'Hi ${firstName}, check out this image!',
    'MMS Campaign',
    $options
);

echo "MMS sent successfully! Campaign ID: " . $response->campaignId . "\n";
```

## Features

- Send SMS messages to single or multiple recipients
- Send MMS messages with images
- Variable substitution in messages
- Type hints for better IDE integration
- Comprehensive error handling
- PSR-7 and PSR-18 compliant

## License

MIT
