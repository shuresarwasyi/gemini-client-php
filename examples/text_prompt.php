<?php

require '../GeminiClient.php';

// Ensure the GEMINI_API_KEY environment variable is set
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    die("Error: GEMINI_API_KEY environment variable not set.\n");
}

$geminiClient = new GeminiClient($apiKey);

echo "<pre>";

try {
    $prompt = "Write a short tagline for a new coffee shop.";
    $response = $geminiClient->sendPrompt($prompt);
    echo "Prompt: " . $prompt . "\n";
    echo "Response:\n" . $response . "\n\n";
} catch (GeminiApiException $e) {
    echo "Error sending text prompt: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}