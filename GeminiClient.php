<?php

/**
 * Exception class for handling Gemini API related errors.
 */
class GeminiApiException extends Exception {}

/**
 * A PHP client for interacting with the Google Gemini API.
 *
 * @author Shures Arwasyi <shuresarwasyi@gmail.com>
 * @version 1.0.0
 * @license Proprietary
 */
class GeminiClient
{
    /**
     * @var string The API key for accessing the Google Gemini API.
     */
    private $apiKey;

    /**
     * @var string The ID of the Gemini model to be used for generating content.
     */
    private $modelId;

    /**
     * @var string The base URL for the Gemini API's streamGenerateContent endpoint.
     */
    private $baseUrl;

    /**
     * @var array An array to store the history of the conversation. Each item is an
     * associative array with 'role' ('user' or 'model') and 'text' or 'inlineData'.
     */
    public $history = array();

    /**
     * @var bool Determines whether the conversation history should be automatically
     * included in each `sendPrompt` request.
     */
    private $includeHistoryOnSend;

    /**
     * @const string Constant for specifying that the desired response type is plain text.
     */
    const RESPONSE_TYPE_TEXT = 'text';

    /**
     * @const string Constant for specifying that the desired response type is a PHP object
     * (representing the decoded JSON response).
     */
    const RESPONSE_TYPE_PHP_OBJECT = 'object';

    /**
     * Constructor for the GeminiClient class.
     *
     * @param string $apiKey The API key for the Google Gemini API.
     * @param string $modelId The ID of the Gemini model to use (default: 'gemini-1.5-pro-vision').
     * @param bool $includeHistory Whether to automatically include conversation history in requests (default: false).
     */
    public function __construct($apiKey, $modelId = 'gemini-1.5-flash', $includeHistory = false)
    {
        $this->apiKey = $apiKey;
        $this->modelId = $modelId;
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:generateContent?key={$this->apiKey}";
        $this->includeHistoryOnSend = $includeHistory;
    }

    /**
     * Attempts to detect the MIME type from a Base64 data URL prefix.
     *
     * @param string $base64String The Base64 encoded data URL.
     * @return string|null The detected MIME type, or null if no valid prefix is found.
     */
    private function _detectMimeTypeFromBase64($base64String)
    {
        $prefixParts = explode(',', $base64String, 2);
        if (isset($prefixParts[0]) && strpos($prefixParts[0], 'data:') === 0) {
            $mimePart = substr($prefixParts[0], 5); // Remove 'data:'
            $mimeParts = explode(';', $mimePart);
            return isset($mimeParts[0]) ? $mimeParts[0] : null;
        }
        return null;
    }

    /**
     * Extracts and decodes a JSON block from a Markdown string.
     *
     * Searches for a JSON code block enclosed within triple backticks (```json ... ```),
     * then attempts to decode it into an associative array.
     *
     * @param string $markdown The Markdown string containing a JSON code block.
     * @return array|string The decoded JSON as an associative array, or an error message if extraction or decoding fails.
     */
    private function _extractJsonFromMarkdown($markdown) {
        // Use regex to find content between ```json and ```
        if (preg_match('/```json\s*(.*?)\s*```/s', $markdown, $matches)) {
            $jsonString = $matches[1];
            $jsonData = $jsonString;
    
            if (json_last_error() === JSON_ERROR_NONE) {
                return $jsonData;
            } else {
                return "Invalid JSON: " . json_last_error_msg();
            }
        } else {
            return "No JSON block found.";
        }
    }

    /**
     * Checks if a string starts with a specific substring (for PHP < 8.0 compatibility).
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for at the beginning.
     * @return bool True if $haystack starts with $needle, false otherwise.
     */
    private function _startsWith($haystack, $needle)
    {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * Sends a prompt to the Gemini API to generate content.
     *
     * @param array|string $prompts A prompt or an array of prompts. Each element can be:
     * - A string representing a text prompt (e.g., "What is the weather like?").
     * - An associative array for text (e.g., `['type' => 'text', 'text' => 'Explain photosynthesis.']`).
     * - An associative array for a file (image or PDF) via path (e.g., `['type' => 'file', 'path' => '/path/to/image.jpg']`).
     * - An associative array for a file (image or PDF) via base64 encoded data (e.g., `['type' => 'file', 'base64' => 'iVBORw0KGgo...', 'mimeType' => 'image/png']` or `['type' => 'file', 'base64' => 'data:image/png;base64,iVBORw0KGgo...']`).
     * - An associative array for a file (image or PDF) via URL (e.g., `['type' => 'file', 'url' => 'http://example.com/image.jpg']`).
     * 
     * Ensure the `mimeType` is correctly specified if no data URL prefix is used.
     * @param string $responseType The desired response type (`GeminiClient::RESPONSE_TYPE_TEXT` or `GeminiClient::RESPONSE_TYPE_PHP_OBJECT`). Defaults to `GeminiClient::RESPONSE_TYPE_TEXT`.
     * @return mixed The API response in the specified format or `null` on error.
     * @throws GeminiApiException If an error occurs during the API call or response processing.
     */
    public function sendPrompt($prompts, $responseType = self::RESPONSE_TYPE_TEXT)
    {
        $contents = array();
        // Handle single string prompt
        if (is_string($prompts)) {
            $prompts = array($prompts); // Convert to an array for consistent processing
        }

        // Include history if the option is enabled
        if ($this->includeHistoryOnSend) {
            foreach ($this->history as $item) {
                $parts = array();
                if (isset($item['text'])) $parts[] = array('text' => $item['text']);
                if (isset($item['inlineData'])) $parts[] = array('inlineData' => $item['inlineData']);
                $contents[] = array('role' => $item['role'], 'parts' => $parts);
            }
        }

        $parts = array();
        foreach ($prompts as $prompt) {
            if (is_string($prompt)) {
                $parts[] = array('text' => $prompt);
                $this->history[] = array('role' => 'user', 'text' => $prompt);
            } elseif (is_array($prompt) && isset($prompt['type'])) {
                if ($prompt['type'] === 'file' && isset($prompt['base64'])) {
                    $mimeType = isset($prompt['mimeType']) ? $prompt['mimeType'] : $this->_detectMimeTypeFromBase64($prompt['base64']);
                    if (!$mimeType) {
                        throw new GeminiApiException("Invalid input: MIME type is required for Base64 files without a data URL prefix (image/* or application/pdf).");
                    }
                    if (!$this->_startsWith($mimeType, 'image/') && $mimeType !== 'application/pdf') {
                        throw new GeminiApiException("Invalid input: Unsupported MIME type for file: {$mimeType}. Supported types are image/* and application/pdf.");
                    }
                    $base64DataParts = explode(',', $prompt['base64'], 2);
                    $base64Data = isset($base64DataParts[1]) ? $base64DataParts[1] : $prompt['base64'];
                    $parts[] = array('inlineData' => array('mimeType' => $mimeType, 'data' => $base64Data));
                    $this->history[] = array('role' => 'user', 'inlineData' => array('mimeType' => $mimeType, 'data' => $base64Data));
                } elseif ($prompt['type'] === 'text' && isset($prompt['text'])) {
                    $parts[] = array('text' => $prompt['text']);
                    $this->history[] = array('role' => 'user', 'text' => $prompt['text']);
                } elseif ($prompt['type'] === 'file' && isset($prompt['url'])) {
                    $fileUrl = $prompt['url'];

                    $fileData = @file_get_contents($fileUrl);
                    if ($fileData === false) {
                        throw new GeminiApiException("Failed to download file from URL: {$fileUrl}");
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_buffer($finfo, $fileData);
                    finfo_close($finfo);

                    if (!$this->_startsWith($mimeType, 'image/') && $mimeType !== 'application/pdf') {
                        throw new GeminiApiException("Invalid input: Unsupported MIME type for file: {$mimeType}. Supported types are image/* and application/pdf.");
                    }

                    $base64Data = base64_encode($fileData);
                    $parts[] = array('inlineData' => array('mimeType' => $mimeType, 'data' => $base64Data));
                    $this->history[] = array('role' => 'user', 'inlineData' => array('mimeType' => $mimeType, 'data' => $base64Data));
                } elseif ($prompt['type'] === 'file' && isset($prompt['path'])) {
                    if (!file_exists($prompt['path'])) {
                        throw new GeminiApiException("Invalid input: File not found at path: {$prompt['path']}");
                    }
                    $mimeType = mime_content_type($prompt['path']);
                    if (!$this->_startsWith($mimeType, 'image/') && $mimeType !== 'application/pdf') {
                        throw new GeminiApiException("Invalid input: Unsupported MIME type for file: {$mimeType}. Supported types are image/* and application/pdf.");
                    }
                    $base64Data = base64_encode(file_get_contents($prompt['path']));
                    $parts[] = array('inlineData' => array('mimeType' => $mimeType, 'data' => $base64Data));
                    $this->history[] = array('role' => 'user', 'inlineData' => array('mimeType' => $mimeType, 'data' => $base64Data));
                }
            }
        }
        $contents[] = array('role' => 'user', 'parts' => $parts);

        // Prepare the request body
        $requestData = array(
            'contents' => $contents,
            'generationConfig' => array(
                'temperature' => 1,
                'responseMimeType' => 'text/plain',
                'topP' => 0.95
            ),
        );

        if ($this->modelId == 'gemini-2.5-flash-preview-04-17') {
            $requestData["generationConfig"]["thinkingConfig"] = array(
                "thinkingBudget" => 8000
            );
        }

        // Initialize cURL session
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a timeout for the request

        // Execute the cURL request
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Handle cURL errors
        if ($error) {
            throw new GeminiApiException("cURL Error: " . $error);
        }

        // Handle HTTP errors
        if ($httpCode !== 200) {
            throw new GeminiApiException("HTTP Error {$httpCode}: " . $response);
        }

        try {
            // Decode the JSON response
            $data = json_decode($response, true);
            // Handle Gemini API errors within the JSON response
            if (isset($data['error'])) {
                throw new GeminiApiException("Gemini API Error (Code: " . $data['error']['code'] . "): " . $data['error']['message']);
            }
            // Extract the generated text answer
            $answer = isset($data['candidates'][0]['content']['parts'][0]['text']) ? $data['candidates'][0]['content']['parts'][0]['text'] : null;
            // Add the model's response to the history
            $this->history[] = array('role' => 'model', 'text' => $answer);

            // Return the response in the desired format
            if ($responseType === self::RESPONSE_TYPE_PHP_OBJECT) {
                $answerJSONStr = $this->_extractJsonFromMarkdown($answer);
                $answerObj = json_decode($answerJSONStr);

                return $answerObj;
            } else {
                return trim($answer);
            }
        } catch (JsonException $e) {
            throw new GeminiApiException("JSON Decoding Error: " . $e->getMessage() . "\nRaw Response: " . $response);
        } catch (Exception $e) {
            throw new GeminiApiException("An unexpected error occurred: " . $e->getMessage());
        }
    }

    /**
     * Returns the current conversation history.
     *
     * @return array An array containing the history of the conversation.
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Clears the current conversation history, removing all stored messages.
     *
     * @return void
     */
    public function clearHistory()
    {
        $this->history = array();
    }
}