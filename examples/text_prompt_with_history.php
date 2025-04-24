<?php

require '../GeminiClient.php';

// Ensure the GEMINI_API_KEY environment variable is set
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    die("Error: GEMINI_API_KEY environment variable not set.\n");
}

$geminiClient = new GeminiClient($apiKey, 'gemini-pro', true); // Enable history

try {
    $prompt1 = "Hello Gemini, how are you today?";
    $response1 = $geminiClient->sendPrompt($prompt1);
    echo "Prompt 1: " . $prompt1 . "\n";
    echo "Response 1:\n" . $response1 . "\n\n";

    $prompt2 = "That's good to hear. What is the capital of Indonesia?";
    $response2 = $geminiClient->sendPrompt($prompt2);
    echo "Prompt 2: " . $prompt2 . "\n";
    echo "Response 2:\n" . $response2 . "\n\n";

    echo "Current History:\n";
    print_r($geminiClient->getHistory());
    echo "\n";

    $geminiClient->clearHistory();
    echo "History cleared.\n";
    echo "Current History after clearing:\n";
    print_r($geminiClient->getHistory());
    echo "\n";

} catch (GeminiApiException $e) {
    echo "Error sending prompt with history: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}