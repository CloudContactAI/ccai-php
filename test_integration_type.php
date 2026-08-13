<?php
require 'vendor/autoload.php';

// Test con ALL (actual)
echo "=== Test 1: integrationType = 'ALL' ===\n";
$config1 = [
    'url' => 'https://webhook.site/test-all',
    'integrationType' => 'ALL',
];
echo "Config con ALL: " . json_encode($config1) . "\n\n";

// Test con DEFAULT (antes)
echo "=== Test 2: integrationType = 'DEFAULT' ===\n";
$config2 = [
    'url' => 'https://webhook.site/test-default',
    'integrationType' => 'DEFAULT',
];
echo "Config con DEFAULT: " . json_encode($config2) . "\n\n";

// Comparar
echo "=== Comparación ===\n";
echo "¿Son iguales? " . (json_encode($config1) === json_encode($config2) ? "SÍ" : "NO") . "\n";
echo "\nNota: El valor de integrationType afecta a qué tipos de eventos se disparan en el webhook.\n";
echo "ALL = todos los eventos\n";
echo "DEFAULT = solo eventos por defecto\n";
