<?php

/**
 * CCAI PHP SDK Integration Tests
 *
 * Exercises all 42 public API methods against the test environment.
 * Exits with code 1 if any test fails.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account as SmsAccount;
use CloudContactAI\CCAI\Email\EmailAccount;
// ---------------------------------------------------------------------------
// Environment variables
// ---------------------------------------------------------------------------
$clientId  = getenv('CCAI_CLIENT_ID')       ?: '';
$apiKey    = getenv('CCAI_API_KEY')          ?: '';
$phone1    = getenv('CCAI_TEST_PHONE')       ?: '';
$phone2    = getenv('CCAI_TEST_PHONE_2')     ?: '';
$phone3    = getenv('CCAI_TEST_PHONE_3')     ?: '';
$email1    = getenv('CCAI_TEST_EMAIL')       ?: '';
$email2    = getenv('CCAI_TEST_EMAIL_2')     ?: '';
$email3    = getenv('CCAI_TEST_EMAIL_3')     ?: '';
$firstName1 = getenv('CCAI_TEST_FIRST_NAME')   ?: 'Docker';
$lastName1  = getenv('CCAI_TEST_LAST_NAME')    ?: 'Test';
$firstName2 = getenv('CCAI_TEST_FIRST_NAME_2') ?: 'Docker2';
$lastName2  = getenv('CCAI_TEST_LAST_NAME_2')  ?: 'Test2';
$firstName3 = getenv('CCAI_TEST_FIRST_NAME_3') ?: 'Docker3';
$lastName3  = getenv('CCAI_TEST_LAST_NAME_3')  ?: 'Test3';
$webhookUrl = getenv('WEBHOOK_URL')            ?: 'https://webhook.site/php-docker-test';

if (empty($clientId) || empty($apiKey)) {
    fwrite(STDERR, "ERROR: CCAI_CLIENT_ID and CCAI_API_KEY environment variables are required.\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Client
// ---------------------------------------------------------------------------
$client = new CCAI([
    'clientId'           => $clientId,
    'apiKey'             => $apiKey,
    'useTestEnvironment' => true,
]);

// ---------------------------------------------------------------------------
// Test image: 1x1 transparent PNG embedded as base64
// ---------------------------------------------------------------------------
$imageB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==';
$imagePath = sys_get_temp_dir() . '/ccai_test.png';
file_put_contents($imagePath, base64_decode($imageB64));

// ---------------------------------------------------------------------------
// Test runner
// ---------------------------------------------------------------------------
$passed = 0;
$failed = 0;

function runTest(string $label, callable $fn, int &$passed, int &$failed): void
{
    try {
        $fn();
        echo "  [PASS] {$label}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$label}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "=== CCAI PHP SDK Integration Tests ===\n\n";
// ---------------------------------------------------------------------------
// SMS Tests (01–06)
// ---------------------------------------------------------------------------
echo "--- SMS ---\n";

runTest('01 SMS sendSingle', function () use ($client, $firstName1, $lastName1, $phone1) {
    $res = $client->sms->sendSingle($firstName1, $lastName1, $phone1, 'Hello ${firstName}!', 'PHP Test 01');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('02 SMS send (1 recipient)', function () use ($client, $firstName1, $lastName1, $phone1) {
    $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
    $res = $client->sms->send($accounts, 'Bulk test ${firstName}', 'PHP Test 02');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('03 SMS send (2 recipients)', function () use ($client, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2) {
    $accounts = [
        new SmsAccount($firstName1, $lastName1, $phone1),
        new SmsAccount($firstName2, $lastName2, $phone2),
    ];
    $res = $client->sms->send($accounts, 'Multi-recipient test ${firstName}', 'PHP Test 03');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('04 SMS send (3 recipients)', function () use ($client, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2, $firstName3, $lastName3, $phone3) {
    $accounts = [
        new SmsAccount($firstName1, $lastName1, $phone1),
        new SmsAccount($firstName2, $lastName2, $phone2),
        new SmsAccount($firstName3, $lastName3, $phone3),
    ];
    $res = $client->sms->send($accounts, 'Triple-recipient test ${firstName}', 'PHP Test 04');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('05 SMS send with data (template variables)', function () use ($client, $firstName1, $lastName1, $phone1) {
    $accounts = [new SmsAccount($firstName1, $lastName1, $phone1, ['city' => 'Miami', 'code' => 'PHP5'])];
    $res = $client->sms->send($accounts, 'Hello ${firstName}, your code is ${code} from ${city}', 'PHP Test 05');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('06 SMS sendSingle with customData', function () use ($client, $firstName1, $lastName1, $phone1) {
    $res = $client->sms->sendSingle($firstName1, $lastName1, $phone1, 'Custom data test', 'PHP Test 06', '{"source":"php-integration"}');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

// ---------------------------------------------------------------------------
// MMS Tests (07–17)
// ---------------------------------------------------------------------------
echo "\n--- MMS ---\n";

$uploadUrl = null;
$fileKey   = null;

runTest('07 MMS getSignedUploadUrl', function () use ($client, &$uploadUrl, &$fileKey) {
    $res = $client->mms->getSignedUploadUrl('php_test.png', 'image/png');
    if (empty($res['signedS3Url'])) {
        throw new RuntimeException('Missing signedS3Url in response');
    }
    $uploadUrl = $res['signedS3Url'];
    $fileKey   = $res['fileKey'];
}, $passed, $failed);

runTest('08 MMS uploadImageToSignedUrl', function () use ($client, &$uploadUrl, $imagePath) {
    if ($uploadUrl === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $ok = $client->mms->uploadImageToSignedUrl($uploadUrl, $imagePath, 'image/png');
    if (!$ok) {
        throw new RuntimeException('Upload returned false');
    }
}, $passed, $failed);

runTest('09 MMS sendSingle', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $res = $client->mms->sendSingle($fileKey, $firstName1, $lastName1, $phone1, 'MMS single test', 'PHP MMS Test 09');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('10 MMS send (1 recipient)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
    $res = $client->mms->send($fileKey, $accounts, 'MMS bulk test', 'PHP MMS Test 10');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('11 MMS send (2 recipients)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $accounts = [
        new SmsAccount($firstName1, $lastName1, $phone1),
        new SmsAccount($firstName2, $lastName2, $phone2),
    ];
    $res = $client->mms->send($fileKey, $accounts, 'MMS 2-recipient test', 'PHP MMS Test 11');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('12 MMS send (3 recipients)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2, $firstName3, $lastName3, $phone3) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $accounts = [
        new SmsAccount($firstName1, $lastName1, $phone1),
        new SmsAccount($firstName2, $lastName2, $phone2),
        new SmsAccount($firstName3, $lastName3, $phone3),
    ];
    $res = $client->mms->send($fileKey, $accounts, 'MMS 3-recipient test', 'PHP MMS Test 12');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('13 MMS send with data (template variables)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $accounts = [new SmsAccount($firstName1, $lastName1, $phone1, ['promo' => 'PHP13'])];
    $res = $client->mms->send($fileKey, $accounts, 'MMS data test promo ${promo}', 'PHP MMS Test 13');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('14 MMS sendSingle with customData', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $res = $client->mms->sendSingle($fileKey, $firstName1, $lastName1, $phone1, 'MMS custom data test', 'PHP MMS Test 14', '{"source":"php-integration"}');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('15 MMS checkFileUploaded', function () use ($client, &$fileKey) {
    if ($fileKey === null) {
        throw new RuntimeException('Dependency test 07 failed — skipping');
    }
    $res = $client->mms->checkFileUploaded($fileKey);
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response');
    }
}, $passed, $failed);

runTest('16 MMS sendWithImage (fresh upload)', function () use ($client, $imagePath, $firstName1, $lastName1, $phone1) {
    $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
    $res = $client->mms->sendWithImage($imagePath, 'image/png', $accounts, 'MMS sendWithImage test', 'PHP MMS Test 16');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('17 MMS sendWithImage (cached, same file)', function () use ($client, $imagePath, $firstName1, $lastName1, $phone1) {
    $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
    $res = $client->mms->sendWithImage($imagePath, 'image/png', $accounts, 'MMS cached image test', 'PHP MMS Test 17');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

// ---------------------------------------------------------------------------
// Email Tests (18–22)
// ---------------------------------------------------------------------------
echo "\n--- Email ---\n";

runTest('18 Email sendSingle', function () use ($client, $firstName1, $lastName1, $email1) {
    $res = $client->email->sendSingle($firstName1, $lastName1, $email1, 'PHP Integration Test 18', '<p>Hello ${firstName}!</p>');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('19 Email send (1 recipient)', function () use ($client, $firstName1, $lastName1, $email1) {
    $accounts = [new EmailAccount($firstName1, $lastName1, $email1)];
    $res = $client->email->send($accounts, 'PHP Integration Test 19', '<p>Hello ${firstName}!</p>', 'noreply@cloudcontactai.com', 'noreply@cloudcontactai.com', 'PHP Test');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('20 Email send (2 recipients)', function () use ($client, $firstName1, $lastName1, $email1, $firstName2, $lastName2, $email2) {
    $accounts = [
        new EmailAccount($firstName1, $lastName1, $email1),
        new EmailAccount($firstName2, $lastName2, $email2),
    ];
    $res = $client->email->send($accounts, 'PHP Integration Test 20', '<p>Hello ${firstName}!</p>', 'noreply@cloudcontactai.com', 'noreply@cloudcontactai.com', 'PHP Test');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('21 Email send (3 recipients)', function () use ($client, $firstName1, $lastName1, $email1, $firstName2, $lastName2, $email2, $firstName3, $lastName3, $email3) {
    $accounts = [
        new EmailAccount($firstName1, $lastName1, $email1),
        new EmailAccount($firstName2, $lastName2, $email2),
        new EmailAccount($firstName3, $lastName3, $email3),
    ];
    $res = $client->email->send($accounts, 'PHP Integration Test 21', '<p>Hello ${firstName}!</p>', 'noreply@cloudcontactai.com', 'noreply@cloudcontactai.com', 'PHP Test');
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('22 Email sendCampaign (full config)', function () use ($client, $firstName1, $lastName1, $email1) {
    $campaign = [
        'accounts'    => [['firstName' => $firstName1, 'lastName' => $lastName1, 'email' => $email1]],
        'subject'     => 'PHP Integration Test 22',
        'title'       => 'PHP Campaign Test 22',
        'message'     => '<h1>Campaign Test</h1><p>Hello ${firstName}, this is a full campaign test.</p>',
        'senderEmail' => 'noreply@cloudcontactai.com',
        'replyEmail'  => 'noreply@cloudcontactai.com',
        'senderName'  => 'PHP Integration',
    ];
    $res = $client->email->sendCampaign($campaign);
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

// ---------------------------------------------------------------------------
// Webhook Tests (23–29)
// ---------------------------------------------------------------------------
echo "\n--- Webhook ---\n";

$webhookId = null;

runTest('23 Webhook register', function () use ($client, $webhookUrl, &$webhookId) {
    $res = $client->webhook->register([
        'url'             => $webhookUrl,
        'method'          => 'POST',
        'integrationType' => 'ALL',
        'secretKey'       => 'php-test-secret-key',
    ]);
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
    $webhookId = (string) ($res['id'] ?? '');
    if (empty($webhookId)) {
        throw new RuntimeException('No id in register response');
    }
}, $passed, $failed);

runTest('24 Webhook list', function () use ($client) {
    $res = $client->webhook->list();
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response');
    }
}, $passed, $failed);

runTest('25 Webhook update', function () use ($client, &$webhookId, $webhookUrl) {
    if (empty($webhookId)) {
        throw new RuntimeException('Dependency test 23 failed — skipping');
    }
    $res = $client->webhook->update($webhookId, [
        'url'             => $webhookUrl . '/updated',
        'method'          => 'POST',
        'integrationType' => 'ALL',
    ]);
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
}, $passed, $failed);

runTest('26 Webhook verifySignature (valid)', function () use ($client, $clientId) {
    $secret    = 'php-test-secret-key';
    $eventHash = 'abc123hash';
    $data      = "{$clientId}:{$eventHash}";
    $expected  = base64_encode(hash_hmac('sha256', $data, $secret, true));
    $ok = $client->webhook->verifySignature($expected, $clientId, $eventHash, $secret);
    if (!$ok) {
        throw new RuntimeException('Valid signature verification returned false');
    }
}, $passed, $failed);

runTest('27 Webhook verifySignature (invalid)', function () use ($client, $clientId) {
    $ok = $client->webhook->verifySignature('invalidsignature==', $clientId, 'somehash', 'wrong-secret');
    if ($ok) {
        throw new RuntimeException('Invalid signature verification returned true');
    }
}, $passed, $failed);

runTest('28 Webhook parseWebhookEvent', function () use ($client) {
    $payload = json_encode([
        'eventType'  => 'SMS_SENT',
        'clientId'   => '1411',
        'eventHash'  => 'abc123hash',
        'campaignId' => 'camp-001',
        'phone'      => '+13055551234',
    ]);
    $event = $client->webhook->parseWebhookEvent($payload);
    if (empty($event['eventType'])) {
        throw new RuntimeException('Missing eventType in parsed event');
    }
}, $passed, $failed);

runTest('29 Webhook delete', function () use ($client, &$webhookId) {
    if (empty($webhookId)) {
        throw new RuntimeException('Dependency test 23 failed — skipping');
    }
    $res = $client->webhook->delete($webhookId);
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response from delete');
    }
}, $passed, $failed);

// ---------------------------------------------------------------------------
// Contact Tests (30–31)
// ---------------------------------------------------------------------------
echo "\n--- Contact ---\n";

runTest('30 Contact setDoNotText (opt-out)', function () use ($client, $phone1) {
    $res = $client->contact->setDoNotText(true, null, $phone1);
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response');
    }
}, $passed, $failed);

runTest('31 Contact setDoNotText (opt-in)', function () use ($client, $phone1) {
    $res = $client->contact->setDoNotText(false, null, $phone1);
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response');
    }
}, $passed, $failed);

// ---------------------------------------------------------------------------
// Brands Tests (32–36)
// ---------------------------------------------------------------------------
echo "\n--- Brands ---\n";

$brandId = null;

runTest('32 Brand.create', function () use ($client, &$brandId) {
    $res = $client->brands->create([
        'legalCompanyName' => 'PHP Test Company LLC',
        'entityType'       => 'PRIVATE_PROFIT',
        'taxId'            => '123456789',
        'taxIdCountry'     => 'US',
        'country'          => 'US',
        'verticalType'     => 'TECHNOLOGY',
        'websiteUrl'       => 'https://test.example.com',
        'street'           => '123 Test St',
        'city'             => 'San Francisco',
        'state'            => 'CA',
        'postalCode'       => '94105',
        'contactFirstName' => 'John',
        'contactLastName'  => 'Doe',
        'contactEmail'     => 'john.doe@test.example.com',
        'contactPhone'     => '+14155551234',
    ]);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in brand create response');
    }
    $brandId = (int) $res['id'];
}, $passed, $failed);

runTest('33 Brand.get', function () use ($client, &$brandId) {
    if ($brandId === null) {
        throw new RuntimeException('Dependency test 32 failed — skipping');
    }
    $res = $client->brands->get($brandId);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in brand get response');
    }
}, $passed, $failed);

runTest('34 Brand.list', function () use ($client) {
    $res = $client->brands->list();
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response');
    }
}, $passed, $failed);

runTest('35 Brand.update', function () use ($client, &$brandId) {
    if ($brandId === null) {
        throw new RuntimeException('Dependency test 32 failed — skipping');
    }
    $res = $client->brands->update($brandId, [
        'city' => 'Los Angeles',
    ]);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in brand update response');
    }
}, $passed, $failed);

runTest('36 Brand.delete', function () use ($client, &$brandId) {
    if ($brandId === null) {
        throw new RuntimeException('Dependency test 32 failed — skipping');
    }
    $client->brands->delete($brandId);
    // 204 No Content — no response body expected
}, $passed, $failed);

// ---------------------------------------------------------------------------
// Campaigns Tests (37–42)
// ---------------------------------------------------------------------------
echo "\n--- Campaigns ---\n";

$campaignBrandId = null;
$campaignId      = null;

runTest('37 Campaign setup — Brand.create', function () use ($client, &$campaignBrandId) {
    $res = $client->brands->create([
        'legalCompanyName' => 'PHP Campaign Test LLC',
        'entityType'       => 'PRIVATE_PROFIT',
        'taxId'            => '987654321',
        'taxIdCountry'     => 'US',
        'country'          => 'US',
        'verticalType'     => 'TECHNOLOGY',
        'websiteUrl'       => 'https://campaign-test.example.com',
        'street'           => '456 Campaign Ave',
        'city'             => 'New York',
        'state'            => 'NY',
        'postalCode'       => '10001',
        'contactFirstName' => 'Jane',
        'contactLastName'  => 'Smith',
        'contactEmail'     => 'jane.smith@campaign-test.example.com',
        'contactPhone'     => '+12125551234',
    ]);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in brand create response');
    }
    $campaignBrandId = (int) $res['id'];
}, $passed, $failed);

runTest('38 Campaign.create', function () use ($client, &$campaignBrandId, &$campaignId) {
    if ($campaignBrandId === null) {
        throw new RuntimeException('Dependency test 37 failed — skipping');
    }
    $res = $client->campaigns->create([
        'brandId'           => $campaignBrandId,
        'useCase'           => 'MARKETING',
        'description'       => 'PHP integration test campaign for marketing messages',
        'messageFlow'       => 'User visits our website and opts in via the sign-up form',
        'hasEmbeddedLinks'  => false,
        'hasEmbeddedPhone'  => false,
        'isAgeGated'        => false,
        'isDirectLending'   => false,
        'optInKeywords'     => ['START', 'YES'],
        'optInMessage'      => 'You have opted in to receive messages. Reply STOP to unsubscribe.',
        'optInProofUrl'     => 'https://campaign-test.example.com/opt-in',
        'helpKeywords'      => ['HELP', 'INFO'],
        'helpMessage'       => 'Reply HELP for assistance. Reply STOP to stop receiving messages.',
        'optOutKeywords'    => ['STOP', 'CANCEL'],
        'optOutMessage'     => 'You have been unsubscribed. Reply STOP to opt out.',
        'sampleMessages'    => [
            'Hello ${firstName}, check out our latest offers! Reply STOP to unsubscribe.',
            'Your special discount is ready! Reply HELP for assistance.',
        ],
    ]);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in campaign create response');
    }
    $campaignId = (int) $res['id'];
}, $passed, $failed);

runTest('39 Campaign.get', function () use ($client, &$campaignId) {
    if ($campaignId === null) {
        throw new RuntimeException('Dependency test 38 failed — skipping');
    }
    $res = $client->campaigns->get($campaignId);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in campaign get response');
    }
}, $passed, $failed);

runTest('40 Campaign.list', function () use ($client) {
    $res = $client->campaigns->list();
    if (!is_array($res)) {
        throw new RuntimeException('Expected array response');
    }
}, $passed, $failed);

runTest('41 Campaign.update', function () use ($client, &$campaignId) {
    if ($campaignId === null) {
        throw new RuntimeException('Dependency test 38 failed — skipping');
    }
    $res = $client->campaigns->update($campaignId, [
        'description' => 'Updated PHP integration test campaign description',
    ]);
    if (empty($res['id'])) {
        throw new RuntimeException('No id in campaign update response');
    }
}, $passed, $failed);

runTest('42 Campaign.delete', function () use ($client, &$campaignId, &$campaignBrandId) {
    if ($campaignId === null) {
        throw new RuntimeException('Dependency test 38 failed — skipping');
    }
    $client->campaigns->delete($campaignId);
    // 204 No Content — no response body expected

    // Clean up the brand created for campaign tests
    if ($campaignBrandId !== null) {
        $client->brands->delete($campaignBrandId);
    }
}, $passed, $failed);

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
$total = $passed + $failed;
echo "\n=== Results: {$passed}/{$total} passed ===\n";

if ($failed > 0) {
    exit(1);
}
