<?php

require '../GeminiClient.php';

// Ensure the GEMINI_API_KEY environment variable is set
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    die("Error: GEMINI_API_KEY environment variable not set.\n");
}

$geminiClient = new GeminiClient($apiKey, 'gemini-pro-vision');

// Replace with the actual path to your image file
$imagePath = './assets/image-1.jpg';

if (file_exists($imagePath)) {
    try {
        $prompt = [
            ['type' => 'text', 'text' => 'What animal is in this image?'],
            ['type' => 'image', 'path' => $imagePath]
        ];
        $response = $geminiClient->sendPrompt($prompt);
        echo "Multi-Prompt (text and image path):\n";
        print_r($prompt);
        echo "\nResponse:\n" . $response . "\n\n";
    } catch (GeminiApiException $e) {
        echo "Error sending multi-prompt: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "General error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Error: Image file not found at " . $imagePath . "\n";
}