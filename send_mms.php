<?php

/**
 * Simple example of sending an MMS message using the CCAI PHP library
 */

require_once __DIR__ . '/vendor/autoload.php';


use CloudContactAI\CCAI\CCAI;

// Replace with your actual credentials
$ccai = new CCAI([
    'clientId' => '2682',
    'apiKey' => 'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJpbmZvQGFsbGNvZGUuY29tIiwiaXNzIjoiY2xvdWRjb250YWN0IiwibmJmIjoxNzE5NDQwMjM2LCJpYXQiOjE3MTk0NDAyMzYsInJvbGUiOiJVU0VSIiwiY2xpZW50SWQiOjI2ODIsImlkIjoyNzY0LCJ0eXBlIjoiQVBJX0tFWSIsImtleV9yYW5kb21faWQiOiI1MGRiOTUzZC1hMjUxLTRmZjMtODI5Yi01NjIyOGRhOGE1YTAifQ.PKVjXYHdjBMum9cTgLzFeY2KIb9b2tjawJ0WXalsb8Bckw1RuxeiYKS1bw5Cc36_Rfmivze0T7r-Zy0PVj2omDLq65io0zkBzIEJRNGDn3gx_AqmBrJ3yGnz9s0WTMr2-F1TFPUByzbj1eSOASIKeI7DGufTA5LDrRclVkz32Oo'
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
                'phone' => '+14156961732'
            ]
        ],
        'Hi ${firstName} ${lastName}, testing a new campaign',
        'MMS Content Test Message'
    );
    
    echo "MMS sent successfully! ID: " . $response->id . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
