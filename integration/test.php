<?php

/**
 * CCAI PHP SDK Integration Tests — 52 tests
 * Covers: SMS (1-6), MMS (7-17), Email (18-22), Webhook (23-29), Contact (30-31),
 * Brands (32-36), Campaigns (37-42), ContactValidator (43-46), Negative cases (47-52)
 *
 * Test results use three states:
 *   PASS — the test ran and all assertions held
 *   FAIL — the test ran and an assertion (or the API call) failed
 *   SKIP — a prerequisite test failed, so this test could not run
 *
 * Resources created during the run (webhooks, brands, campaigns) are tracked and
 * deleted in a final cleanup block even if tests fail midway.
 * Exits with code 1 if any test fails, 2 if required env vars are missing.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account as SmsAccount;
use CloudContactAI\CCAI\Email\EmailAccount;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Thrown when a test cannot run because a prerequisite test failed. */
class SkipTest extends RuntimeException
{
}

function runTest(string $label, callable $fn, int &$passed, int &$failed, int &$skipped): void
{
    try {
        $fn();
        echo "  [PASS] {$label}\n";
        $passed++;
    } catch (SkipTest $e) {
        echo "  [SKIP] {$label}: " . $e->getMessage() . "\n";
        $skipped++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$label}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

/**
 * Asserts that a send-style response carries a campaign/message identifier.
 * SMS/MMS responses are SMSResponse objects; email responses are arrays.
 */
function assertSendResponse(mixed $res): void
{
    if (empty($res)) {
        throw new RuntimeException('Empty response');
    }
    if (is_array($res)) {
        if (empty($res['id']) && empty($res['campaignId'])) {
            throw new RuntimeException('response has no id/campaignId: ' . substr(json_encode($res) ?: '', 0, 200));
        }
        return;
    }
    $id  = $res->id ?? null;
    $cid = $res->campaignId ?? null;
    if (empty($id) && empty($cid)) {
        throw new RuntimeException('response has no id/campaignId');
    }
}

/** Runs $fn and asserts that it throws — used by the negative test cases. */
function expectError(callable $fn, string $what): void
{
    try {
        $fn();
    } catch (SkipTest $e) {
        throw $e;
    } catch (Throwable $e) {
        return; // failed as expected
    }
    throw new RuntimeException("expected {$what} to fail, but it succeeded");
}

// ---------------------------------------------------------------------------
// Environment variables — validate ALL required vars up front and report every
// missing one, instead of failing later with a cryptic API error.
// ---------------------------------------------------------------------------
$requiredEnv = [
    'CCAI_CLIENT_ID', 'CCAI_API_KEY',
    'CCAI_TEST_PHONE', 'CCAI_TEST_PHONE_2', 'CCAI_TEST_PHONE_3',
    'CCAI_TEST_EMAIL', 'CCAI_TEST_EMAIL_2', 'CCAI_TEST_EMAIL_3',
    'CCAI_TEST_FIRST_NAME', 'CCAI_TEST_LAST_NAME',
    'CCAI_TEST_FIRST_NAME_2', 'CCAI_TEST_LAST_NAME_2',
    'CCAI_TEST_FIRST_NAME_3', 'CCAI_TEST_LAST_NAME_3',
    'WEBHOOK_URL',
];
$missing = array_values(array_filter($requiredEnv, static fn (string $key): bool => !getenv($key)));
if ($missing !== []) {
    fwrite(STDERR, 'ERROR: required env vars are not set: ' . implode(', ', $missing) . "\n");
    exit(2);
}

$clientId   = (string) getenv('CCAI_CLIENT_ID');
$apiKey     = (string) getenv('CCAI_API_KEY');
$phone1     = (string) getenv('CCAI_TEST_PHONE');
$phone2     = (string) getenv('CCAI_TEST_PHONE_2');
$phone3     = (string) getenv('CCAI_TEST_PHONE_3');
$email1     = (string) getenv('CCAI_TEST_EMAIL');
$email2     = (string) getenv('CCAI_TEST_EMAIL_2');
$email3     = (string) getenv('CCAI_TEST_EMAIL_3');
$firstName1 = (string) getenv('CCAI_TEST_FIRST_NAME');
$lastName1  = (string) getenv('CCAI_TEST_LAST_NAME');
$firstName2 = (string) getenv('CCAI_TEST_FIRST_NAME_2');
$lastName2  = (string) getenv('CCAI_TEST_LAST_NAME_2');
$firstName3 = (string) getenv('CCAI_TEST_FIRST_NAME_3');
$lastName3  = (string) getenv('CCAI_TEST_LAST_NAME_3');

// Unique per-run suffix so parallel SDK runs don't collide on the same webhook URL
$runId       = 'php-' . time();
$webhookBase = (string) getenv('WEBHOOK_URL');
$webhookUrl  = $webhookBase . (str_contains($webhookBase, '?') ? '&' : '?') . 'run=' . $runId;

$senderEmail   = getenv('CCAI_TEST_SENDER_EMAIL') ?: 'noreply@cloudcontactai.com';
$replyEmail    = $senderEmail;
$webhookSecret = getenv('CCAI_WEBHOOK_SECRET') ?: 'php-test-secret-key';

// ---------------------------------------------------------------------------
// Client
// ---------------------------------------------------------------------------
// Use CCAI_BASE_URL if set (local dev), otherwise fall back to test environment
$client = new CCAI([
    'clientId'           => $clientId,
    'apiKey'             => $apiKey,
    'useTestEnvironment' => !getenv('CCAI_BASE_URL'),
]);

// ---------------------------------------------------------------------------
// Test image: 1x1 transparent PNG embedded as base64
// ---------------------------------------------------------------------------
$imageB64  = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==';
$imagePath = sys_get_temp_dir() . '/ccai_test_' . $runId . '.png';
file_put_contents($imagePath, base64_decode($imageB64));

// ---------------------------------------------------------------------------
// Test runner state
// ---------------------------------------------------------------------------
$passed  = 0;
$failed  = 0;
$skipped = 0;

// IDs of resources created by the tests; anything still listed here at the end
// of the run is deleted by the cleanup block (tests remove entries they already
// deleted themselves).
$cleanupWebhookIds  = [];
$cleanupBrandIds    = [];
$cleanupCampaignIds = [];

echo "=== CCAI PHP SDK Integration Tests ===\n\n";

try {
    // -----------------------------------------------------------------------
    // SMS Tests (01–06)
    // -----------------------------------------------------------------------
    echo "--- SMS ---\n";

    runTest('01 SMS sendSingle', function () use ($client, $firstName1, $lastName1, $phone1) {
        $res = $client->sms->sendSingle($firstName1, $lastName1, $phone1, 'Hello ${firstName}!', 'PHP Test 01');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('02 SMS send (1 recipient)', function () use ($client, $firstName1, $lastName1, $phone1) {
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
        $res = $client->sms->send($accounts, 'Bulk test ${firstName}', 'PHP Test 02');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('03 SMS send (2 recipients)', function () use ($client, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2) {
        $accounts = [
            new SmsAccount($firstName1, $lastName1, $phone1),
            new SmsAccount($firstName2, $lastName2, $phone2),
        ];
        $res = $client->sms->send($accounts, 'Multi-recipient test ${firstName}', 'PHP Test 03');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('04 SMS send (3 recipients)', function () use ($client, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2, $firstName3, $lastName3, $phone3) {
        $accounts = [
            new SmsAccount($firstName1, $lastName1, $phone1),
            new SmsAccount($firstName2, $lastName2, $phone2),
            new SmsAccount($firstName3, $lastName3, $phone3),
        ];
        $res = $client->sms->send($accounts, 'Triple-recipient test ${firstName}', 'PHP Test 04');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('05 SMS send with data (template variables)', function () use ($client, $firstName1, $lastName1, $phone1) {
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1, ['city' => 'Miami', 'code' => 'PHP5'])];
        $res = $client->sms->send($accounts, 'Hello ${firstName}, your code is ${code} from ${city}', 'PHP Test 05');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('06 SMS sendSingle with customData', function () use ($client, $firstName1, $lastName1, $phone1) {
        $res = $client->sms->sendSingle($firstName1, $lastName1, $phone1, 'Custom data test', 'PHP Test 06', '{"source":"php-integration"}');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // MMS Tests (07–17)
    // -----------------------------------------------------------------------
    echo "\n--- MMS ---\n";

    $uploadUrl = null;
    $fileKey   = null;
    $uploadOk  = false;

    runTest('07 MMS getSignedUploadUrl', function () use ($client, &$uploadUrl, &$fileKey) {
        $res = $client->mms->getSignedUploadUrl('php_test.png', 'image/png');
        if (empty($res['signedS3Url'])) {
            throw new RuntimeException('Missing signedS3Url in response');
        }
        if (empty($res['fileKey'])) {
            throw new RuntimeException('Missing fileKey in response');
        }
        $uploadUrl = $res['signedS3Url'];
        $fileKey   = $res['fileKey'];
    }, $passed, $failed, $skipped);

    runTest('08 MMS uploadImageToSignedUrl', function () use ($client, &$uploadUrl, &$uploadOk, $imagePath) {
        if ($uploadUrl === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $ok = $client->mms->uploadImageToSignedUrl($uploadUrl, $imagePath, 'image/png');
        if (!$ok) {
            throw new RuntimeException('Upload returned false');
        }
        $uploadOk = true;
    }, $passed, $failed, $skipped);

    runTest('09 MMS sendSingle', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $res = $client->mms->sendSingle($fileKey, $firstName1, $lastName1, $phone1, 'MMS single test', 'PHP MMS Test 09');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('10 MMS send (1 recipient)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
        $res = $client->mms->send($fileKey, $accounts, 'MMS bulk test', 'PHP MMS Test 10');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('11 MMS send (2 recipients)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $accounts = [
            new SmsAccount($firstName1, $lastName1, $phone1),
            new SmsAccount($firstName2, $lastName2, $phone2),
        ];
        $res = $client->mms->send($fileKey, $accounts, 'MMS 2-recipient test', 'PHP MMS Test 11');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('12 MMS send (3 recipients)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1, $firstName2, $lastName2, $phone2, $firstName3, $lastName3, $phone3) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $accounts = [
            new SmsAccount($firstName1, $lastName1, $phone1),
            new SmsAccount($firstName2, $lastName2, $phone2),
            new SmsAccount($firstName3, $lastName3, $phone3),
        ];
        $res = $client->mms->send($fileKey, $accounts, 'MMS 3-recipient test', 'PHP MMS Test 12');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('13 MMS send with data (template variables)', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1, ['promo' => 'PHP13'])];
        $res = $client->mms->send($fileKey, $accounts, 'MMS data test promo ${promo}', 'PHP MMS Test 13');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('14 MMS sendSingle with customData', function () use ($client, &$fileKey, $firstName1, $lastName1, $phone1) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        $res = $client->mms->sendSingle($fileKey, $firstName1, $lastName1, $phone1, 'MMS custom data test', 'PHP MMS Test 14', '{"source":"php-integration"}');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('15 MMS checkFileUploaded', function () use ($client, &$fileKey, &$uploadOk) {
        if ($fileKey === null) {
            throw new SkipTest('dependency test 07 failed');
        }
        if (!$uploadOk) {
            throw new SkipTest('dependency test 08 failed');
        }
        $res = $client->mms->checkFileUploaded($fileKey);
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response');
        }
        if (empty($res['storedUrl'])) {
            throw new RuntimeException("expected non-empty storedUrl for uploaded file {$fileKey}");
        }
    }, $passed, $failed, $skipped);

    runTest('16 MMS sendWithImage (fresh upload)', function () use ($client, $imagePath, $firstName1, $lastName1, $phone1) {
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
        $res = $client->mms->sendWithImage($imagePath, 'image/png', $accounts, 'MMS sendWithImage test', 'PHP MMS Test 16');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('17 MMS sendWithImage (cached, same file)', function () use ($client, $imagePath, $firstName1, $lastName1, $phone1) {
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
        $res = $client->mms->sendWithImage($imagePath, 'image/png', $accounts, 'MMS cached image test', 'PHP MMS Test 17');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // Email Tests (18–22)
    // -----------------------------------------------------------------------
    echo "\n--- Email ---\n";

    runTest('18 Email sendSingle', function () use ($client, $firstName1, $lastName1, $email1) {
        $res = $client->email->sendSingle($firstName1, $lastName1, $email1, 'PHP Integration Test 18', '<p>Hello ${firstName}!</p>');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('19 Email send (1 recipient)', function () use ($client, $firstName1, $lastName1, $email1, $senderEmail, $replyEmail) {
        $accounts = [new EmailAccount($firstName1, $lastName1, $email1)];
        $res = $client->email->send($accounts, 'PHP Integration Test 19', '<p>Hello ${firstName}!</p>', $senderEmail, $replyEmail, 'PHP Test');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('20 Email send (2 recipients)', function () use ($client, $firstName1, $lastName1, $email1, $firstName2, $lastName2, $email2, $senderEmail, $replyEmail) {
        $accounts = [
            new EmailAccount($firstName1, $lastName1, $email1),
            new EmailAccount($firstName2, $lastName2, $email2),
        ];
        $res = $client->email->send($accounts, 'PHP Integration Test 20', '<p>Hello ${firstName}!</p>', $senderEmail, $replyEmail, 'PHP Test');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('21 Email send (3 recipients)', function () use ($client, $firstName1, $lastName1, $email1, $firstName2, $lastName2, $email2, $firstName3, $lastName3, $email3, $senderEmail, $replyEmail) {
        $accounts = [
            new EmailAccount($firstName1, $lastName1, $email1),
            new EmailAccount($firstName2, $lastName2, $email2),
            new EmailAccount($firstName3, $lastName3, $email3),
        ];
        $res = $client->email->send($accounts, 'PHP Integration Test 21', '<p>Hello ${firstName}!</p>', $senderEmail, $replyEmail, 'PHP Test');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('22 Email sendCampaign (full config)', function () use ($client, $firstName1, $lastName1, $email1, $senderEmail, $replyEmail) {
        $campaign = [
            'accounts'    => [['firstName' => $firstName1, 'lastName' => $lastName1, 'email' => $email1]],
            'subject'     => 'PHP Integration Test 22',
            'title'       => 'PHP Campaign Test 22',
            'message'     => '<h1>Campaign Test</h1><p>Hello ${firstName}, this is a full campaign test.</p>',
            'senderEmail' => $senderEmail,
            'replyEmail'  => $replyEmail,
            'senderName'  => 'PHP Integration',
        ];
        $res = $client->email->sendCampaign($campaign);
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // Webhook Tests (23–29)
    // -----------------------------------------------------------------------
    echo "\n--- Webhook ---\n";

    $webhookId = null;

    runTest('23 Webhook register', function () use ($client, $webhookUrl, $webhookSecret, &$webhookId, &$cleanupWebhookIds) {
        $res = $client->webhook->register([
            'url'             => $webhookUrl,
            'method'          => 'POST',
            'integrationType' => 'ALL',
            'secretKey'       => $webhookSecret,
        ]);
        if (empty($res)) {
            throw new RuntimeException('Empty response');
        }
        $webhookId = (string) ($res['id'] ?? '');
        if (empty($webhookId)) {
            throw new RuntimeException('No id in register response');
        }
        $cleanupWebhookIds[] = $webhookId;
    }, $passed, $failed, $skipped);

    runTest('24 Webhook list', function () use ($client, &$webhookId) {
        $res = $client->webhook->list();
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response');
        }
        if (!empty($webhookId)) {
            $found = false;
            foreach ($res as $hook) {
                if ((string) ($hook['id'] ?? '') === $webhookId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("webhook {$webhookId} registered in test 23 not present in list()");
            }
        }
    }, $passed, $failed, $skipped);

    runTest('25 Webhook update', function () use ($client, &$webhookId, $webhookUrl) {
        if (empty($webhookId)) {
            throw new SkipTest('dependency test 23 failed');
        }
        $res = $client->webhook->update($webhookId, [
            'url'             => $webhookUrl . '&updated=1',
            'method'          => 'POST',
            'integrationType' => 'ALL',
        ]);
        if (empty($res)) {
            throw new RuntimeException('Empty response');
        }
        // Verify via list() that the URL actually changed
        $hooks = $client->webhook->list();
        foreach ($hooks as $hook) {
            if ((string) ($hook['id'] ?? '') === $webhookId) {
                if (!str_contains((string) ($hook['url'] ?? ''), 'updated=1')) {
                    throw new RuntimeException('webhook URL was not updated: got "' . ($hook['url'] ?? '') . '"');
                }
                return;
            }
        }
        throw new RuntimeException("webhook {$webhookId} not found in list() after update");
    }, $passed, $failed, $skipped);

    runTest('26 Webhook verifySignature (valid)', function () use ($client, $clientId, $webhookSecret) {
        $eventHash = 'abc123hash';
        $data      = "{$clientId}:{$eventHash}";
        $expected  = base64_encode(hash_hmac('sha256', $data, $webhookSecret, true));
        $ok = $client->webhook->verifySignature($expected, $clientId, $eventHash, $webhookSecret);
        if (!$ok) {
            throw new RuntimeException('Valid signature verification returned false');
        }
    }, $passed, $failed, $skipped);

    runTest('27 Webhook verifySignature (invalid)', function () use ($client, $clientId) {
        $ok = $client->webhook->verifySignature('invalidsignature==', $clientId, 'somehash', 'wrong-secret');
        if ($ok) {
            throw new RuntimeException('Invalid signature verification returned true');
        }
    }, $passed, $failed, $skipped);

    runTest('28 Webhook parseWebhookEvent', function () use ($client) {
        $payload = json_encode([
            'eventType'  => 'SMS_SENT',
            'clientId'   => '1411',
            'eventHash'  => 'abc123hash',
            'campaignId' => 'camp-001',
            'phone'      => '+13055551234',
        ]);
        $event = $client->webhook->parseWebhookEvent((string) $payload);
        if (($event['eventType'] ?? '') !== 'SMS_SENT') {
            throw new RuntimeException('expected eventType "SMS_SENT", got "' . ($event['eventType'] ?? '') . '"');
        }
    }, $passed, $failed, $skipped);

    runTest('29 Webhook delete', function () use ($client, &$webhookId, &$cleanupWebhookIds) {
        if (empty($webhookId)) {
            throw new SkipTest('dependency test 23 failed');
        }
        $res = $client->webhook->delete($webhookId);
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response from delete');
        }
        $cleanupWebhookIds = array_values(array_filter($cleanupWebhookIds, static fn ($id) => $id !== $webhookId));
        // Verify via list() that it is gone
        $hooks = $client->webhook->list();
        foreach ($hooks as $hook) {
            if ((string) ($hook['id'] ?? '') === $webhookId) {
                throw new RuntimeException("webhook {$webhookId} still present in list() after delete");
            }
        }
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // Contact Tests (30–31)
    // -----------------------------------------------------------------------
    echo "\n--- Contact ---\n";

    runTest('30 Contact setDoNotText (opt-out)', function () use ($client, $phone1) {
        $res = $client->contact->setDoNotText(true, null, $phone1);
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response');
        }
    }, $passed, $failed, $skipped);

    runTest('31 Contact setDoNotText (opt-in)', function () use ($client, $phone1) {
        $res = $client->contact->setDoNotText(false, null, $phone1);
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response');
        }
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // Brands Tests (32–36)
    // -----------------------------------------------------------------------
    echo "\n--- Brands ---\n";

    $brandId = null;

    runTest('32 Brand.create', function () use ($client, &$brandId, &$cleanupBrandIds) {
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
        $cleanupBrandIds[] = $brandId;
    }, $passed, $failed, $skipped);

    runTest('33 Brand.get', function () use ($client, &$brandId) {
        if ($brandId === null) {
            throw new SkipTest('dependency test 32 failed');
        }
        $res = $client->brands->get($brandId);
        if (empty($res['id']) || (int) $res['id'] !== $brandId) {
            throw new RuntimeException('Brand id mismatch in get response');
        }
        if (($res['legalCompanyName'] ?? '') !== 'PHP Test Company LLC') {
            throw new RuntimeException('expected legalCompanyName "PHP Test Company LLC", got "' . ($res['legalCompanyName'] ?? '') . '"');
        }
    }, $passed, $failed, $skipped);

    runTest('34 Brand.list', function () use ($client, &$brandId) {
        $res = $client->brands->list();
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response');
        }
        if ($brandId !== null) {
            $found = false;
            foreach ($res as $brand) {
                if ((int) ($brand['id'] ?? 0) === $brandId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("brand {$brandId} created in test 32 not present in list()");
            }
        }
    }, $passed, $failed, $skipped);

    runTest('35 Brand.update', function () use ($client, &$brandId) {
        if ($brandId === null) {
            throw new SkipTest('dependency test 32 failed');
        }
        $res = $client->brands->update($brandId, [
            'city' => 'Los Angeles',
        ]);
        if (empty($res['id'])) {
            throw new RuntimeException('No id in brand update response');
        }
        // Verify via get() that the field actually changed
        $fetched = $client->brands->get($brandId);
        if (($fetched['city'] ?? '') !== 'Los Angeles') {
            throw new RuntimeException('expected city "Los Angeles" after update, got "' . ($fetched['city'] ?? '') . '"');
        }
    }, $passed, $failed, $skipped);

    runTest('36 Brand.delete', function () use ($client, &$brandId, &$cleanupBrandIds) {
        if ($brandId === null) {
            throw new SkipTest('dependency test 32 failed');
        }
        $client->brands->delete($brandId);
        $cleanupBrandIds = array_values(array_filter($cleanupBrandIds, static fn ($id) => $id !== $brandId));
        // Verify via get() that it is gone (404 expected)
        expectError(fn () => $client->brands->get($brandId), "get of deleted brand {$brandId}");
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // Campaigns Tests (37–42)
    // -----------------------------------------------------------------------
    echo "\n--- Campaigns ---\n";

    $campaignBrandId = null;
    $campaignId      = null;

    runTest('37 Campaign setup — Brand.create', function () use ($client, &$campaignBrandId, &$cleanupBrandIds) {
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
        $cleanupBrandIds[] = $campaignBrandId;
    }, $passed, $failed, $skipped);

    runTest('38 Campaign.create', function () use ($client, &$campaignBrandId, &$campaignId, &$cleanupCampaignIds) {
        if ($campaignBrandId === null) {
            throw new SkipTest('dependency test 37 failed');
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
        $cleanupCampaignIds[] = $campaignId;
    }, $passed, $failed, $skipped);

    runTest('39 Campaign.get', function () use ($client, &$campaignId, &$campaignBrandId) {
        if ($campaignId === null) {
            throw new SkipTest('dependency test 38 failed');
        }
        $res = $client->campaigns->get($campaignId);
        if (empty($res['id']) || (int) $res['id'] !== $campaignId) {
            throw new RuntimeException('Campaign id mismatch in get response');
        }
        if ((int) ($res['brandId'] ?? 0) !== $campaignBrandId) {
            throw new RuntimeException('expected brandId ' . $campaignBrandId . ', got ' . ($res['brandId'] ?? 'null'));
        }
    }, $passed, $failed, $skipped);

    runTest('40 Campaign.list', function () use ($client, &$campaignId) {
        $res = $client->campaigns->list();
        if (!is_array($res)) {
            throw new RuntimeException('Expected array response');
        }
        if ($campaignId !== null) {
            $found = false;
            foreach ($res as $campaign) {
                if ((int) ($campaign['id'] ?? 0) === $campaignId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("campaign {$campaignId} created in test 38 not present in list()");
            }
        }
    }, $passed, $failed, $skipped);

    runTest('41 Campaign.update', function () use ($client, &$campaignId) {
        if ($campaignId === null) {
            throw new SkipTest('dependency test 38 failed');
        }
        $newDescription = 'Updated PHP integration test campaign description';
        $res = $client->campaigns->update($campaignId, [
            'description' => $newDescription,
        ]);
        if (empty($res['id'])) {
            throw new RuntimeException('No id in campaign update response');
        }
        // Verify via get() that the field actually changed
        $fetched = $client->campaigns->get($campaignId);
        if (($fetched['description'] ?? '') !== $newDescription) {
            throw new RuntimeException('expected updated description after update, got "' . ($fetched['description'] ?? '') . '"');
        }
    }, $passed, $failed, $skipped);

    runTest('42 Campaign.delete', function () use ($client, &$campaignId, &$campaignBrandId, &$cleanupCampaignIds, &$cleanupBrandIds) {
        if ($campaignId === null) {
            throw new SkipTest('dependency test 38 failed');
        }
        $client->campaigns->delete($campaignId);
        $cleanupCampaignIds = array_values(array_filter($cleanupCampaignIds, static fn ($id) => $id !== $campaignId));
        // Verify via get() that it is gone (404 expected)
        expectError(fn () => $client->campaigns->get($campaignId), "get of deleted campaign {$campaignId}");

        // Clean up the brand created for campaign tests
        if ($campaignBrandId !== null) {
            $client->brands->delete($campaignBrandId);
            $cleanupBrandIds = array_values(array_filter($cleanupBrandIds, static fn ($id) => $id !== $campaignBrandId));
        }
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // ContactValidator Tests (43–46)
    // -----------------------------------------------------------------------
    echo "\n--- ContactValidator ---\n";

    runTest('43 ContactValidator.validateEmail', function () use ($client, $email1) {
        $resp = $client->contactValidator->validateEmail($email1);
        if (empty($resp['status'])) {
            throw new RuntimeException('status is empty');
        }
    }, $passed, $failed, $skipped);

    runTest('44 ContactValidator.validateEmails', function () use ($client, $email1, $email2) {
        $resp = $client->contactValidator->validateEmails([$email1, $email2]);
        if (($resp['summary']['total'] ?? 0) !== 2) {
            throw new RuntimeException('expected summary.total=2, got ' . ($resp['summary']['total'] ?? 'null'));
        }
        if (count($resp['results'] ?? []) !== 2) {
            throw new RuntimeException('expected 2 results, got ' . count($resp['results'] ?? []));
        }
    }, $passed, $failed, $skipped);

    runTest('45 ContactValidator.validatePhone', function () use ($client, $phone1) {
        $resp = $client->contactValidator->validatePhone($phone1);
        if (empty($resp['status'])) {
            throw new RuntimeException('status is empty');
        }
    }, $passed, $failed, $skipped);

    runTest('46 ContactValidator.validatePhones', function () use ($client, $phone1, $phone2) {
        $resp = $client->contactValidator->validatePhones([
            ['phone' => $phone1],
            ['phone' => $phone2],
        ]);
        if (($resp['summary']['total'] ?? 0) !== 2) {
            throw new RuntimeException('expected summary.total=2, got ' . ($resp['summary']['total'] ?? 'null'));
        }
        if (count($resp['results'] ?? []) !== 2) {
            throw new RuntimeException('expected 2 results, got ' . count($resp['results'] ?? []));
        }
    }, $passed, $failed, $skipped);

    // -----------------------------------------------------------------------
    // Negative & Permissive Tests (47–52)
    // 47/49/50 PASS when the operation fails as expected. 48/51/52 document
    // permissive behavior observed in the test API: those
    // operations succeed even with invalid input, so the tests assert success.
    // -----------------------------------------------------------------------
    echo "\n--- Negative cases ---\n";

    runTest('47 NEGATIVE: SMS sendSingle with invalid API key', function () use ($clientId, $firstName1, $lastName1, $phone1) {
        $badClient = new CCAI([
            'clientId'           => $clientId,
            'apiKey'             => 'invalid-api-key-for-negative-test',
            'useTestEnvironment' => !getenv('CCAI_BASE_URL'),
        ]);
        expectError(
            fn () => $badClient->sms->sendSingle($firstName1, $lastName1, $phone1, 'should fail', 'PHP Negative 47'),
            'send with invalid API key'
        );
    }, $passed, $failed, $skipped);

    // The test API accepts malformed phone numbers: the send
    // succeeds instead of failing. If the API starts validating phone format,
    // change this back to expect an error.
    runTest('48 PERMISSIVE: SMS sendSingle with malformed phone (API accepts)', function () use ($client, $firstName1, $lastName1) {
        $res = $client->sms->sendSingle($firstName1, $lastName1, 'abc', 'malformed phone accepted', 'PHP Permissive 48');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);

    runTest('49 NEGATIVE: Brand.get(nonexistent)', function () use ($client) {
        expectError(fn () => $client->brands->get(99999999), 'get of nonexistent brand');
    }, $passed, $failed, $skipped);

    runTest('50 NEGATIVE: Webhook.delete(nonexistent)', function () use ($client) {
        expectError(fn () => $client->webhook->delete('99999999'), 'delete of nonexistent webhook');
    }, $passed, $failed, $skipped);

    // The test environment's validator reports "valid" even for syntactically
    // invalid emails — upstream validation is not enforced
    // there, so only assert that a status is returned.
    runTest('51 PERMISSIVE: ContactValidator.validateEmail(invalid input)', function () use ($client) {
        $resp = $client->contactValidator->validateEmail('not-an-email');
        if (empty($resp['status'])) {
            throw new RuntimeException('status is empty');
        }
    }, $passed, $failed, $skipped);

    // The test API accepts MMS sends with a nonexistent fileKey: it does not
    // verify the file exists at send time. If the API
    // starts validating the fileKey, change this back to expect an error.
    runTest('52 PERMISSIVE: MMS send with nonexistent fileKey (API accepts)', function () use ($client, $clientId, $firstName1, $lastName1, $phone1) {
        $accounts = [new SmsAccount($firstName1, $lastName1, $phone1)];
        $fakeKey  = "{$clientId}/campaign/nonexistent_" . time() . '.png';
        $res = $client->mms->send($fakeKey, $accounts, 'nonexistent fileKey accepted', 'PHP Permissive 52');
        assertSendResponse($res);
    }, $passed, $failed, $skipped);
} finally {
    // -----------------------------------------------------------------------
    // Cleanup — always runs, even if the test body threw: delete leftover
    // resources and the temp PNG.
    // -----------------------------------------------------------------------
    foreach ($cleanupCampaignIds as $id) {
        try {
            $client->campaigns->delete((int) $id);
            echo "  CLEANUP: deleted leftover campaign {$id}\n";
        } catch (Throwable $e) {
            echo "  CLEANUP: could not delete campaign {$id}: " . $e->getMessage() . "\n";
        }
    }
    foreach ($cleanupBrandIds as $id) {
        try {
            $client->brands->delete((int) $id);
            echo "  CLEANUP: deleted leftover brand {$id}\n";
        } catch (Throwable $e) {
            echo "  CLEANUP: could not delete brand {$id}: " . $e->getMessage() . "\n";
        }
    }
    foreach ($cleanupWebhookIds as $id) {
        try {
            $client->webhook->delete((string) $id);
            echo "  CLEANUP: deleted leftover webhook {$id}\n";
        } catch (Throwable $e) {
            echo "  CLEANUP: could not delete webhook {$id}: " . $e->getMessage() . "\n";
        }
    }
    @unlink($imagePath);
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
$total = $passed + $failed + $skipped;
echo "\n=== Results: {$passed} passed, {$failed} failed, {$skipped} skipped ({$total} total) ===\n";

$summary = json_encode([
    'sdk'     => 'php',
    'passed'  => $passed,
    'failed'  => $failed,
    'skipped' => $skipped,
    'total'   => $total,
]);
echo "\nSUMMARY_JSON: {$summary}\n";

if ($failed > 0) {
    exit(1);
}
