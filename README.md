# CCAI PHP Client

A PHP client for interacting with the CloudContactAI API, with support for SMS, MMS, email campaigns, webhooks, contact management, brand registration for TCR verification, and campaign registration for TCR carrier vetting.

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
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Email\Account;
use CloudContactAI\CCAI\Email\EmailCampaign;
use CloudContactAI\CCAI\Email\EmailOptions;

// Initialize the client
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Send a single email
$response = $ccai->email->sendSingle(
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com',
    subject: 'Welcome to Our Service',
    message: '<p>Hello John,</p><p>Thank you for signing up!</p>',
    senderEmail: 'noreply@yourcompany.com',
    replyEmail: 'support@yourcompany.com',
    senderName: 'Your Company',
    title: 'Welcome Email'
);

echo "Email sent successfully!\n";

// Send email campaign to multiple recipients
$accounts = [
    new Account('John', 'Doe', 'john@example.com'),
    new Account('Jane', 'Smith', 'jane@example.com')
];

$campaign = new EmailCampaign(
    subject: 'Monthly Newsletter',
    title: 'July 2025 Newsletter',
    message: '<h1>Hello ${firstName}!</h1><p>Monthly updates...</p>',
    senderEmail: 'newsletter@yourcompany.com',
    replyEmail: 'support@yourcompany.com',
    senderName: 'Your Company Newsletter',
    accounts: $accounts
);

// Schedule for future delivery
$tomorrow = new DateTime('tomorrow 10:00:00');
$campaign->scheduledTimestamp = $tomorrow->format('c');
$campaign->scheduledTimezone = 'America/New_York';

// Add progress tracking
$options = new EmailOptions(
    timeout: 60,
    onProgress: function($status) {
        echo "Progress: $status\n";
    }
);

$response = $ccai->email->sendCampaign($campaign, $options);
echo "Campaign sent successfully!\n";
```

### Managing Contacts

Manage opt-out preferences for contacts.

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Opt a contact out of text messages (by phone number)
$result = $ccai->contact->setDoNotText(true, null, '+15551234567');
echo "Opted out: " . json_encode($result) . "\n";

// Opt a contact back in
$ccai->contact->setDoNotText(false, null, '+15551234567');

// Opt out by contactId
$ccai->contact->setDoNotText(true, 'contact-abc-123', null);
```

### Contact Validator

Validate email addresses and phone numbers.

> Bulk endpoints accept up to 50 contacts per request and are processed server-side in chunks.

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Validate a single email
$emailResult = $ccai->contactValidator->validateEmail('user@example.com');
echo "Status: " . $emailResult['status'] . "\n"; // "valid" | "invalid" | "risky"

// Validate multiple emails (up to 50, processed server-side in chunks)
$bulkEmails = $ccai->contactValidator->validateEmails([
    'user@example.com',
    'bad@invalid.xyz'
]);
echo "Total: " . $bulkEmails['summary']['total'] . "\n"; // 2
echo "Valid: " . $bulkEmails['summary']['valid'] . "\n"; // 1

// Validate a single phone number
$phoneResult = $ccai->contactValidator->validatePhone('+15551234567', 'US');
echo "Status: " . $phoneResult['status'] . "\n"; // "valid" | "invalid" | "landline"

// Validate multiple phone numbers (up to 50, processed server-side in chunks)
$bulkPhones = $ccai->contactValidator->validatePhones([
    ['phone' => '+15551234567'],
    ['phone' => '+15559876543', 'countryCode' => 'US']
]);
echo "Landline: " . $bulkPhones['summary']['landline'] . "\n"; // 1
```

### Brand Registration

Register and manage brands for TCR verification.

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Create a brand
$brand = $ccai->brands->create([
    'legalCompanyName' => 'Collect.org Inc.',
    'dba'              => 'Collect',
    'entityType'       => 'NON_PROFIT',
    'taxId'            => '123456789',
    'taxIdCountry'     => 'US',
    'country'          => 'US',
    'verticalType'     => 'NON_PROFIT',
    'websiteUrl'       => 'https://www.collect.org',
    'street'           => '123 Main Street',
    'city'             => 'San Francisco',
    'state'            => 'CA',
    'postalCode'       => '94105',
    'contactFirstName' => 'Jane',
    'contactLastName'  => 'Doe',
    'contactEmail'     => 'jane@collect.org',
    'contactPhone'     => '+14155551234',
]);
echo "Brand created with ID: " . $brand['id'] . "\n";

// Get a brand by ID
$fetched = $ccai->brands->get($brand['id']);
echo "Website match score: " . ($fetched['websiteMatchScore'] ?? 'pending') . "\n";

// List all brands
$brands = $ccai->brands->list();
echo "Found " . count($brands) . " brand(s)\n";

// Update a brand (partial update)
$ccai->brands->update($brand['id'], [
    'street' => '456 Oak Avenue',
    'city'   => 'Los Angeles',
]);

// Delete a brand
$ccai->brands->delete($brand['id']);
```

**Entity Types:** `PRIVATE_PROFIT`, `PUBLIC_PROFIT`, `NON_PROFIT`, `GOVERNMENT`, `SOLE_PROPRIETOR`

> Note: `PUBLIC_PROFIT` entities require `stockSymbol` and `stockExchange` fields.

**Vertical Types:** `AUTOMOTIVE`, `AGRICULTURE`, `BANKING`, `COMMUNICATION`, `CONSTRUCTION`, `EDUCATION`, `ENERGY`, `ENTERTAINMENT`, `GOVERNMENT`, `HEALTHCARE`, `HOSPITALITY`, `INSURANCE`, `LEGAL`, `MANUFACTURING`, `NON_PROFIT`, `PROFESSIONAL`, `REAL_ESTATE`, `RETAIL`, `TECHNOLOGY`, `TRANSPORTATION`

### Campaign Registration

Register and manage campaigns for TCR carrier vetting.

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;

$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Create a campaign
$campaign = $ccai->campaigns->create([
    'brandId'          => 1,
    'useCase'          => 'MIXED',
    'subUseCases'      => ['CUSTOMER_CARE', 'TWO_FACTOR_AUTHENTICATION', 'ACCOUNT_NOTIFICATION'],
    'description'      => 'Security codes and support messaging.',
    'messageFlow'      => 'Users opt-in via signup form at https://example.com/signup',
    'hasEmbeddedLinks' => true,
    'hasEmbeddedPhone' => false,
    'isAgeGated'       => false,
    'isDirectLending'  => false,
    'optInKeywords'    => ['START'],
    'optInMessage'     => 'Welcome! Reply STOP to cancel.',
    'optInProofUrl'    => 'https://example.com/opt-in-proof.png',
    'helpKeywords'     => ['HELP'],
    'helpMessage'      => 'For HELP email support@example.com.',
    'optOutKeywords'   => ['STOP'],
    'optOutMessage'    => 'STOP received. You are unsubscribed.',
    'sampleMessages'   => [
        'Your code is 554321. Reply STOP to cancel.',
        'Your ticket has been updated. Reply HELP for info.',
    ],
]);
echo "Campaign created with ID: " . $campaign['id'] . "\n";

// Get a campaign by ID
$fetched = $ccai->campaigns->get($campaign['id']);

// List all campaigns
$campaigns = $ccai->campaigns->list();
echo "Found " . count($campaigns) . " campaign(s)\n";

// Update a campaign (partial update)
$ccai->campaigns->update($campaign['id'], [
    'description' => 'Updated description.',
]);

// Delete a campaign
$ccai->campaigns->delete($campaign['id']);
```

**Use Cases:** `TWO_FACTOR_AUTHENTICATION`, `ACCOUNT_NOTIFICATION`, `CUSTOMER_CARE`, `DELIVERY_NOTIFICATION`, `FRAUD_ALERT`, `HIGHER_EDUCATION`, `LOW_VOLUME_MIXED`, `MARKETING`, `MIXED`, `POLLING_VOTING`, `PUBLIC_SERVICE_ANNOUNCEMENT`, `SECURITY_ALERT`

> Note: `MIXED` and `LOW_VOLUME_MIXED` campaigns require 2–3 `subUseCases`.

**Sub-Use Cases:** `TWO_FACTOR_AUTHENTICATION`, `ACCOUNT_NOTIFICATION`, `CUSTOMER_CARE`, `DELIVERY_NOTIFICATION`, `FRAUD_ALERT`, `MARKETING`, `POLLING_VOTING`

### Webhooks

```php
<?php

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\Webhook\WebhookConfig;
use CloudContactAI\CCAI\Webhook\WebhookEventType;

// Initialize the client
$ccai = new CCAI([
    'clientId' => 'YOUR-CLIENT-ID',
    'apiKey' => 'YOUR-API-KEY'
]);

// Example 1: Register a webhook with auto-generated secret
// If secret is not provided, the server will auto-generate one
$config = new WebhookConfig(
    url: 'https://your-domain.com/api/ccai-webhook',
    events: [WebhookEventType::MESSAGE_SENT, WebhookEventType::MESSAGE_RECEIVED]
    // secret is optional - server will auto-generate and return it
);
$webhook = $ccai->webhook->register($config);

echo "Webhook registered with ID: {$webhook['id']}\n";
echo "Auto-generated Secret: {$webhook['secretKey']}\n";

// Example 2: Register a webhook with a custom secret
$configCustom = new WebhookConfig(
    url: 'https://your-domain.com/api/ccai-webhook-v2',
    secret: 'your-custom-secret-key',
    events: [WebhookEventType::MESSAGE_SENT, WebhookEventType::MESSAGE_RECEIVED]
);
$webhookWithCustomSecret = $ccai->webhook->register($configCustom);

echo "Webhook with custom secret registered: {$webhookWithCustomSecret['id']}\n";

// List all webhooks
$webhooks = $ccai->webhook->list();
echo "Found " . count($webhooks) . " webhooks\n";

// Update a webhook
$updated = $ccai->webhook->update($webhook['id'], [
    'url' => 'https://your-domain.com/api/new-webhook-endpoint'
]);

echo "Webhook updated: {$updated['url']}\n";

// Delete a webhook
$ccai->webhook->delete($webhook['id']);
echo "Webhook deleted\n";

// Verify webhook signature in your HTTP handler
$signature = $_SERVER['HTTP_X_CCAI_SIGNATURE'] ?? '';
$body = file_get_contents('php://input');
$secret = 'your-webhook-secret-key'; // Use the secret returned during registration

// Parse the webhook payload to get client_id and event_hash
$payload = json_decode($body, true);
$clientId = getenv('CCAI_CLIENT_ID');
$eventHash = $payload['eventHash'] ?? '';

if ($ccai->webhook->verifySignature($signature, $clientId, $eventHash, $secret)) {
    // Signature is valid, process the webhook
    $event = $ccai->webhook->parseWebhookEvent($body);
    echo "Webhook event type: {$event['eventType']}\n";
    echo "Webhook data: " . json_encode($event['data']) . "\n";
} else {
    http_response_code(401);
    echo "Invalid signature\n";
    exit;
}
```

### Example Files

Run the example files:

```bash
# Basic email sending
php send_email.php

# Advanced email campaigns with HTML templates and scheduling
php email_campaign_examples.php

# Webhook management and handling
php webhook_example.php
```

## Example Files

This repository includes example files for sending SMS, MMS, and Email messages:

- `send_sms.php` - Example of sending SMS messages
- `send_mms.php` - Example of sending MMS messages with an image
- `send_email.php` - Example of sending email messages

## Features

- Send SMS messages to single or multiple recipients
- Send MMS messages with images (automatic S3 upload)
- Send Email campaigns with HTML content
- Schedule emails for future delivery
- Manage contact opt-out preferences (setDoNotText)
- Validate email addresses (valid/invalid/risky) and phone numbers (valid/invalid/landline)
- Brand registration and management for TCR verification
- Campaign registration and management for TCR carrier vetting
- Webhook management: register, update, list, delete
- Webhook event handling for web frameworks
- Webhook signature verification (HMAC-SHA256 with Base64 encoding)
- Template variable substitution (`${firstName}`, `${lastName}`)
- Progress tracking callbacks
- Type hints for better IDE integration
- Comprehensive error handling
- PSR-7 and PSR-18 compliant

## Removed Functionality

The following methods have been removed as they do not exist in the backend API:
- `SMS::getCampaignStatus()` - Use backend API directly for campaign status
- `Email::getCampaignStatus()` - Use backend API directly for campaign status

## License

MIT
