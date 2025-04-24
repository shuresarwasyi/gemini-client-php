<?php

require '../GeminiClient.php';

// Ensure the GEMINI_API_KEY environment variable is set
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    die("Error: GEMINI_API_KEY environment variable not set.\n");
}

$geminiClient = new GeminiClient($apiKey);

// Replace with the actual path to your image file
$imagePath = './assets/image-1.jpg';

echo "<pre>";

if (file_exists($imagePath)) {
    try {
        $prompt = [
            ['type' => 'file', 'path' => $imagePath],
            ['type' => 'text', 'text' => 'Describe this image in detail.']
        ];
        $response = $geminiClient->sendPrompt($prompt);
        echo "Prompt (image path): " . $imagePath . "\n";
        echo "Response:\n" . $response . "\n\n";
    } catch (GeminiApiException $e) {
        echo "Error sending image prompt (path): " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "General error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Error: Image file not found at " . $imagePath . "\n";
}