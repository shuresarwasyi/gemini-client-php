<?php

require '../GeminiClient.php';

// Ensure the GEMINI_API_KEY environment variable is set
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    die("Error: GEMINI_API_KEY environment variable not set.\n");
}

$geminiClient = new GeminiClient($apiKey, 'gemini-pro-vision');

// Replace with the actual Base64 encoded data of your image file
$base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';
$mimeType = 'image/png';

try {
    $prompt = [
        ['type' => 'image', 'base64' => $base64Image, 'mimeType' => $mimeType],
        ['type' => 'text', 'text' => 'What is depicted in this image?']
    ];
    $response = $geminiClient->sendPrompt($prompt);
    echo "Prompt (image base64):\n";
    print_r($prompt);
    echo "\nResponse:\n" . $response . "\n\n";
} catch (GeminiApiException $e) {
    echo "Error sending image prompt (base64): " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}