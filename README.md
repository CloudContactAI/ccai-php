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

/**
 * Simple example of sending an MMS message using the CCAI PHP library
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

// Replace with your actual credentials
$ccai = new CCAI([
    'clientId' => getenv('CCAI_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
    'apiKey' => getenv('CCAI_API_KEY') ?: 'YOUR_API_KEY'
]);

// Path to the image file you want to send
$filename = 'imagePHP.jpg';
$imagePath = __DIR__ . '/imagePHP.jpg';
$contentType = 'image/jpeg';

try {
    // Send an MMS to a single recipient
    $response = $ccai->mms->sendWithImage(
        $imagePath,
        $contentType,
        [
            [
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'phone' => '+15555555555'
            ]
        ],
        'Hi ${firstName} ${lastName}, testing a new campaign',
        'MMS Content Test Message'
    );
    
    echo "MMS sent successfully! ID: " . $response->id . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Sending Email

```php
php send_email.php
```

## Example Files

This repository includes example files for sending SMS, MMS, and Email messages:

- `send_sms.php` - Example of sending SMS messages
- `send_mms.php` - Example of sending MMS messages with an image
- `send_email.php` - Example of sending email messages

## Features

- Send SMS messages to single or multiple recipients
- Send MMS messages with images
- Send Email messages
- Variable substitution in messages
- Type hints for better IDE integration
- Comprehensive error handling
- PSR-7 and PSR-18 compliant

## License

MIT
