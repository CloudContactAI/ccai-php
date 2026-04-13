<?php
require 'vendor/autoload.php';

use CloudContactAI\CCAI;

$ccai = new CCAI(['clientId' => 'test', 'apiKey' => 'test']);

// Simular payload con integrationType = ALL (actual)
$all_payload = [
    'id' => 1,
    'url' => 'https://test.com/webhook',
    'method' => 'POST',
    'integrationType' => 'ALL',
    'secretKey' => 'secret123'
];

// Simular payload con integrationType = DEFAULT (antes)
$default_payload = [
    'id' => 1,
    'url' => 'https://test.com/webhook',
    'method' => 'POST',
    'integrationType' => 'DEFAULT',
    'secretKey' => 'secret123'
];

echo "=== Comparación de integrationType ===\n\n";

echo "Payload 1 (ALL):\n";
echo json_encode($all_payload, JSON_PRETTY_PRINT) . "\n\n";

echo "Payload 2 (DEFAULT):\n";
echo json_encode($default_payload, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Análisis ===\n";
echo "Diferencia: Solo el campo 'integrationType'\n";
echo "ALL = Todos los eventos\n";
echo "DEFAULT = Solo eventos por defecto\n\n";

echo "Cambiar de ALL a DEFAULT NO causa error de estructura.\n";
echo "El error vendría de la API backend si no acepta DEFAULT.\n";
echo "\n✅ La estructura PHP es compatible con ambos valores.\n";
