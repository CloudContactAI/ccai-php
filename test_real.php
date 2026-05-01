<?php

/**
 * test_real.php - Prueba real de todas las funcionalidades del SDK PHP de CCAI
 *
 * Ejecutar desde la carpeta ccai-php:
 *   C:\xampp\php\php.exe test_real.php
 */

require 'vendor/autoload.php';

use CloudContactAI\CCAI\CCAI;
use CloudContactAI\CCAI\SMS\Account;
use CloudContactAI\CCAI\Email\EmailAccount;

// Cargar .env si existe (solo para desarrollo local)
// Intenta cargar desde la carpeta padre (SDKs) primero, luego desde la local
$envPaths = [
    dirname(__DIR__) . '/.env',           // SDKs/.env (global) - PRIMERO
    __DIR__ . '/.env',                    // ccai-php/.env (local)
];

foreach ($envPaths as $envFile) {
    if (file_exists($envFile)) {
        echo "[DEBUG] Loading .env from: $envFile\n";
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!getenv($key)) {
                putenv("$key=$val");
            }
        }
        break; // Solo carga desde el primer archivo encontrado
    }
}

$clientId = getenv('CCAI_CLIENT_ID') ?: '1411';
$apiKey = getenv('CCAI_API_KEY') ?: '';

// Debug: Show which credentials are being used
echo "[DEBUG] CLIENT_ID: $clientId\n";
echo "[DEBUG] API_KEY: " . (strlen($apiKey) > 0 ? '****' . substr($apiKey, -4) : 'NOT SET') . "\n\n";

$ccai = new CCAI([
    'clientId'           => $clientId,
    'apiKey'             => $apiKey,
    'useTestEnvironment' => true,
]);

$phone  = getenv('CCAI_TEST_PHONE')       ?: '+13055551234';
$email  = getenv('CCAI_TEST_EMAIL')       ?: 'dlopez@allcode.com';
$fname   = getenv('CCAI_TEST_FIRST_NAME')  ?: 'Deyner';
$lname   = getenv('CCAI_TEST_LAST_NAME')   ?: 'Lopez';
$webhookUrl = getenv('WEBHOOK_URL') ?: 'https://webhook.site/php-sdk-test';
$existingWebhookId = getenv('EXISTING_WEBHOOK_ID') ?: '';
$createdWebhookId = null;
$emailCampaignId  = null;
$smsCampaignId    = null;

echo "=== CCAI PHP SDK — Prueba real completa ===\n";
echo "Entorno: " . ($ccai->isTestEnvironment() ? 'TEST' : 'PRODUCCIÓN') . "\n";
echo "Base URL: " . $ccai->getBaseUrl() . "\n\n";

// ─────────────────────────────────────────────────────────────────────────────
echo "── SMS ──────────────────────────────────────────────────────────\n";

// 1. SMS sendSingle
echo "1. sms->sendSingle()\n";
try {
    $res = $ccai->sms->sendSingle(
        firstName: $fname,
        lastName:  $lname,
        phone:     $phone,
        message:   "Hola \${firstName}, prueba sendSingle del SDK PHP!",
        title:     'PHP SDK Test - sendSingle'
    );
    echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 2. SMS send (masivo)
echo "2. sms->send() masivo\n";
try {
    $res = $ccai->sms->send(
        accounts: [new Account($fname, $lname, $phone)],
        message:  "Hola \${firstName}, prueba send() masivo del SDK PHP!",
        title:    'PHP SDK Test - send masivo'
    );
    echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
    $smsCampaignId = $res->campaignId ?? $res->id ?? null;
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 2b. SMS send con data (variables de plantilla) y customData (payload webhook)
echo "2b. sms->send() con data y customData\n";
try {
    $account2b = new Account(
        firstName:  $fname,
        lastName:   $lname,
        phone:      $phone,
        data:       ['city' => 'Miami', 'plan' => 'premium'],
        customData: '{"source":"php-sdk-test","version":"1.0"}'
    );
    $res = $ccai->sms->send(
        accounts: [$account2b],
        message:  'Hola ${firstName} ${lastName}, te escribimos desde ${city}. Plan: ${plan}',
        title:    'PHP SDK Test - data y customData'
    );
    echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
    echo "   ✔ data enviado: city={$account2b->data['city']} | plan={$account2b->data['plan']}\n";
    echo "   ✔ customData → messageData (wire): {$account2b->customData}\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n── MMS ──────────────────────────────────────────────────────────\n";

// Imagen PNG 1x1 pixel para las pruebas
$uniqueFileName = 'ccai_test_php_' . time() . '.png';
$testImagePath = sys_get_temp_dir() . '/' . $uniqueFileName;
file_put_contents($testImagePath, base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg=='
));

$signedUrl = null;
$fileKey   = null;

// 3. MMS getSignedUploadUrl
echo "3. mms->getSignedUploadUrl()\n";
try {
    $res       = $ccai->mms->getSignedUploadUrl($uniqueFileName, 'image/png');
    $signedUrl = $res['signedS3Url'] ?? null;
    $fileKey   = $res['fileKey']    ?? null;
    echo "   ✔ fileKey: {$fileKey}\n";
    echo "   ✔ signedUrl: " . substr($signedUrl, 0, 70) . "...\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 4. MMS uploadImageToSignedUrl
echo "4. mms->uploadImageToSignedUrl()\n";
if ($signedUrl) {
    try {
        $ok = $ccai->mms->uploadImageToSignedUrl($signedUrl, $testImagePath, 'image/png');
        echo "   ✔ Upload exitoso: " . ($ok ? 'true' : 'false') . "\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin signedUrl del paso anterior, omitido\n";
}

// 5. MMS send (con fileKey ya subido)
echo "5. mms->send()\n";
if ($fileKey) {
    try {
        $res = $ccai->mms->send(
            pictureFileKey: $fileKey,
            accounts:       [new Account($fname, $lname, $phone)],
            message:        'Hola ${firstName}, prueba MMS send() del SDK PHP!',
            title:          'PHP SDK Test - MMS send'
        );
        echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin fileKey del paso anterior, omitido\n";
}

// 6. MMS sendSingle (con fileKey ya subido)
echo "6. mms->sendSingle()\n";
if ($fileKey) {
    try {
        $res = $ccai->mms->sendSingle(
            pictureFileKey: $fileKey,
            firstName:      $fname,
            lastName:       $lname,
            phone:          $phone,
            message:        'Hola ${firstName}, prueba MMS sendSingle() del SDK PHP!',
            title:          'PHP SDK Test - MMS sendSingle'
        );
        echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin fileKey del paso anterior, omitido\n";
}

// 6a. MMS send con data (variables de plantilla)
echo "6a. mms->send() con data\n";
if ($fileKey) {
    try {
        $account6a = new Account(
            firstName:  $fname,
            lastName:   $lname,
            phone:      $phone,
            data:       ['promo_code' => 'SAVE20', 'tier' => 'gold'],
        );
        $res = $ccai->mms->send(
            pictureFileKey: $fileKey,
            accounts:       [$account6a],
            message:        'Hola ${firstName}, usa el código ${promo_code}! Tier: ${tier}',
            title:          'PHP SDK Test - MMS con Data'
        );
        echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
        echo "   ✔ data enviado: promo_code={$account6a->data['promo_code']} | tier={$account6a->data['tier']}\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin fileKey del paso anterior, omitido\n";
}

// 6b. MMS send con customData (messageData para webhooks)
echo "6b. mms->send() con customData\n";
if ($fileKey) {
    try {
        $account6b = new Account(
            firstName:  $fname,
            lastName:   $lname,
            phone:      $phone,
            customData: '{"campaign":"MMS-Promo","timestamp":"2026-04-17"}'
        );
        $res = $ccai->mms->send(
            pictureFileKey: $fileKey,
            accounts:       [$account6b],
            message:        'MMS con metadata de webhook - PHP SDK test!',
            title:          'PHP SDK Test - MMS con CustomData'
        );
        echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
        echo "   ✔ customData → messageData (wire): {$account6b->customData}\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin fileKey del paso anterior, omitido\n";
}

// 6c. MMS checkFileUploaded (verificar que el upload manual fue exitoso)
echo "6c. mms->checkFileUploaded()\n";
if ($fileKey) {
    try {
        $res = $ccai->mms->checkFileUploaded($fileKey);
        $storedUrl = $res['storedUrl'] ?? '';
        if (!empty($storedUrl)) {
            echo "   ✔ Imagen encontrada: {$storedUrl}\n";
        } else {
            echo "   ⚠ Imagen no encontrada (storedUrl vacío)\n";
        }
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin fileKey del paso 3, omitido\n";
}

// 7. MMS sendWithImage (flujo completo: upload + send)
echo "7. mms->sendWithImage()\n";
try {
    $res = $ccai->mms->sendWithImage(
        imagePath:   $testImagePath,
        contentType: 'image/png',
        accounts:    [new Account($fname, $lname, $phone)],
        message:     'Hola ${firstName}, prueba MMS sendWithImage() del SDK PHP!',
        title:       'PHP SDK Test - MMS sendWithImage'
    );
    echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId}\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 7b. MMS sendWithImage con MD5 cache (segunda vez — debería ser cache hit)
echo "7b. mms->sendWithImage() — segunda vez (cache hit esperado)\n";
try {
    // Crear la misma imagen de nuevo (mismo contenido = mismo MD5)
    file_put_contents($testImagePath, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg=='
    ));
    $res = $ccai->mms->sendWithImage(
        imagePath:   $testImagePath,
        contentType: 'image/png',
        accounts:    [new Account($fname, $lname, $phone)],
        message:     'Hola ${firstName}, prueba MMS sendWithImage() cache hit del SDK PHP!',
        title:       'PHP SDK Test - MMS sendWithImage cache'
    );
    echo "   ✔ ID: {$res->id} | campaignId: {$res->campaignId} (MD5 cache)\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

@unlink($testImagePath);

// ─────────────────────────────────────────────────────────────────────────────
echo "\n── EMAIL ────────────────────────────────────────────────────────\n";

// 8. Email sendSingle
echo "8. email->sendSingle()\n";
try {
    $res = $ccai->email->sendSingle(
        firstName:   $fname,
        lastName:    $lname,
        email:       $email,
        subject:     'PHP SDK Test - sendSingle',
        htmlContent: '<h1>Hola Deyner!</h1><p>Prueba de sendSingle del SDK PHP de CCAI.</p>',
        senderEmail: 'no-reply@allcode.com',
        replyEmail:  'no-reply@allcode.com',
        senderName:  'CCAI PHP SDK'
    );
    $emailCampaignId = $res['id'] ?? $res['campaignId'] ?? null;
    echo "   ✔ ID: {$emailCampaignId} | status: " . ($res['status'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 9. Email send (masivo)
echo "9. email->send() masivo\n";
try {
    $res = $ccai->email->send(
        accounts:    [new EmailAccount($fname, $lname, $email)],
        subject:     'PHP SDK Test - send masivo',
        htmlContent: '<h1>Hola!</h1><p>Prueba de send() masivo del SDK PHP de CCAI.</p>',
        senderEmail: 'no-reply@allcode.com',
        replyEmail:  'no-reply@allcode.com',
        senderName:  'CCAI PHP SDK'
    );
    echo "   ✔ ID: " . ($res['id'] ?? 'N/A') . " | status: " . ($res['status'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 9b. Email send con data y customAccountId
echo "9b. email->send() con data y customAccountId\n";
try {
    $account9b = new EmailAccount(
        $fname, $lname, $email,
        customAccountId: 'EXT-PHP-001',
        data: ['city' => 'Miami', 'plan' => 'enterprise']
    );
    $res = $ccai->email->send(
        accounts:    [$account9b],
        subject:     'PHP SDK Test - data y customAccountId',
        htmlContent: '<h1>Hola ${firstName} desde ${city}!</h1>',
        senderEmail: 'no-reply@allcode.com',
        replyEmail:  'no-reply@allcode.com',
        senderName:  'CCAI PHP SDK'
    );
    echo "   ✔ ID: " . ($res['id'] ?? 'N/A') . " | status: " . ($res['status'] ?? 'N/A') . "\n";
    echo "   ✔ customAccountId enviado: EXT-PHP-001\n";
    echo "   ✔ data enviado: city=Miami | plan=enterprise\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n── WEBHOOK ──────────────────────────────────────────────────────\n";

echo "📍 Webhook URL: {$webhookUrl}\n";
if (strpos($webhookUrl, 'ngrok') !== false) {
    echo "   ✓ ngrok URL detectada - probando con servidor local\n";
} elseif (strpos($webhookUrl, 'webhook.site') !== false) {
    echo "   ℹ webhook.site URL - sin verificación de firma\n";
}

// 11. Webhook register
echo "11. webhook->register()\n";
if ($existingWebhookId) {
    echo "   ⚠ Reutilizando webhook ID existente: {$existingWebhookId}\n";
    $createdWebhookId = $existingWebhookId;
    echo "   ✔ ID: {$createdWebhookId} (reutilizado)\n";
} else {
    try {
        $res = $ccai->webhook->register([
            'url' => $webhookUrl,
        ]);
        $createdWebhookId = $res['id'] ?? null;
        echo "   ✔ ID: {$createdWebhookId} | url: {$res['url']}\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
}

// 12. Webhook list
echo "12. webhook->list()\n";
try {
    $webhooks = $ccai->webhook->list();
    echo "   ✔ " . count($webhooks) . " webhook(s) registrado(s)\n";
    foreach ($webhooks as $wh) {
        echo "     - ID: " . ($wh['id'] ?? 'N/A') . " | URL: " . ($wh['url'] ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 13. Webhook update
echo "13. webhook->update()\n";
if ($createdWebhookId) {
    try {
        $res = $ccai->webhook->update($createdWebhookId, [
            'url' => 'https://webhook.site/php-sdk-test-updated',
        ]);
        echo "   ✔ ID: {$res['id']} | url: {$res['url']}\n";
    } catch (\Exception $e) {
        echo "   ✘ {$e->getMessage()}\n";
    }
} else {
    echo "   ⚠ Sin webhook ID del paso anterior, omitido\n";
}

// 14. Webhook verifySignature (local, sin llamada a API)
echo "14. webhook->verifySignature() [local]\n";
$webhook_client_id = $clientId;
$webhook_event_hash = 'event-hash-abc123';
$webhook_secret = 'mi-secreto';
$data = "$webhook_client_id:$webhook_event_hash";
$hmac = hash_hmac('sha256', $data, $webhook_secret, true);  // raw binary
$validSig = base64_encode($hmac);
$valid = $ccai->webhook->verifySignature($validSig, $webhook_client_id, $webhook_event_hash, $webhook_secret);
$invalid = $ccai->webhook->verifySignature('firma-incorrecta', $webhook_client_id, $webhook_event_hash, $webhook_secret);
echo "   ✔ Firma válida: "   . ($valid   ? 'true' : 'false') . " (esperado: true)\n";
echo "   ✔ Firma inválida: " . ($invalid ? 'true' : 'false') . " (esperado: false)\n";

// 15. Webhook parseWebhookEvent (local, sin llamada a API)
echo "15. webhook->parseWebhookEvent() [local]\n";
$event = $ccai->webhook->parseWebhookEvent('{"eventType":"message.sent","eventHash":"hash-abc123","data":{"To":"+15551234567","Message":"Hello"}}');
echo "   ✔ Evento: {$event['eventType']} | To: {$event['data']['To']}\n";

// 16. Webhook delete
// No eliminar el webhook — mantenerlo para reutilizar en próximas ejecuciones
echo "16. webhook->delete() - NO ELIMINAR para reutilizar en próximas ejecuciones\n";
if ($createdWebhookId && !$existingWebhookId) {
    echo "   ⚠ Webhook ID: {$createdWebhookId} — mantener para ejecutar con: EXISTING_WEBHOOK_ID={$createdWebhookId}\n";
} elseif ($existingWebhookId) {
    echo "   ⚠ Webhook reutilizado (ID: {$existingWebhookId}) — NO eliminar\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n── CONTACT ──────────────────────────────────────────────────────\n";

// 17. Contact setDoNotText (opt-out)
echo "17. contact->setDoNotText() opt-out\n";
try {
    $res = $ccai->contact->setDoNotText(true, phone: $phone);
    echo "   ✔ doNotText: " . ($res['doNotText'] ? 'true' : 'false') . " | phone: {$res['phone']}\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// 18. Contact setDoNotText (opt-in)
echo "18. contact->setDoNotText() opt-in\n";
try {
    $res = $ccai->contact->setDoNotText(false, phone: $phone);
    echo "   ✔ doNotText: " . ($res['doNotText'] ? 'true' : 'false') . " | phone: {$res['phone']}\n";
} catch (\Exception $e) {
    echo "   ✘ {$e->getMessage()}\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== Fin ===\n";
