# Gemini PHP Client Library

This PHP library provides a convenient way to interact with the Google Gemini API. It supports sending text and media (image, PDF) prompts, managing conversation history, and selecting the response format.

## Features

* **Text and Multimodal Prompts:** Send text-based queries and prompts including images and PDF documents (via file path, base64 encoded data, or file URL).
* **Multi-Prompt Support:** Send multiple prompts (text and/or media) within a single request.
* **Conversation History Management:** Optionally maintain and include conversation history in subsequent requests.
* **Response Format Selection:** Choose to receive the API response as plain text or a PHP object (for accessing raw JSON data).
* **Clear and Object-Oriented Interface:** Encapsulates API interactions within a dedicated `GeminiClient` class.

## Prerequisites

* PHP 5.6 or higher
* `curl` extension enabled
* `json` extension enabled
* `fileinfo` extension enabled (recommended for automatic MIME type detection for file paths)
* Google Gemini API Key: Obtainable from [Google Cloud AI Studio](https://ai.google.dev/) or [Google Cloud Vertex AI](https://cloud.google.com/vertex-ai/docs/generative-ai/learn/models#gemini-models).
* **Recommended:** Set the `GEMINI_API_KEY` environment variable for secure API key management.

## Installation

1.  **Download the Repository:**
    Download the entire repository containing the `GeminiClient.php` file (e.g., by cloning the Git repository or downloading a ZIP archive).

2.  **Copy the GeminiClient Folder:**
    Copy the **GeminiClient folder** (which contains the `GeminiClient.php` file) into a suitable directory within your project. Common locations include `./lib/`, `./libraries/`, or a `vendor` directory.

3.  **Set API Key:**

    * **Using Environment Variable (Recommended):**
        Set the `GEMINI_API_KEY` environment variable in your system or application environment. How you do this depends on your operating system and setup. Examples:
        * **Linux/macOS:** In your terminal, you might use:
          ```bash
          export GEMINI_API_KEY="YOUR_API_KEY"
          ```
          or set it in your `.bashrc`, `.zshrc`, etc.
        * **Windows:**
            * **To set persistently (recommended):** Open Command Prompt as Administrator and run:
              ```bash
              setx GEMINI_API_KEY "YOUR_API_KEY" /M
              ```
              (Using `/M` sets the variable at the system level, requiring Administrator privileges but making it available to all users.)
              Alternatively, to set for the current user only (no admin rights needed):
              ```bash
              setx GEMINI_API_KEY "YOUR_API_KEY"
              ```
            * **To set temporarily (for the current Command Prompt session only):** Open Command Prompt and run:
              ```bash
              set GEMINI_API_KEY "YOUR_API_KEY"
              ```

    * **Directly in Code (Less Secure):**
        You can pass your API key directly when initializing the `GeminiClient`. **This is not recommended for production environments.**
        ```php
        $apiKey = 'YOUR_API_KEY';
        $geminiClient = new GeminiClient($apiKey);
        ```

## Usage Examples

For detailed usage examples, please refer to the files within the `examples` folder in this repository.

## `GeminiClient` Class Methods

* **`__construct(string $apiKey, string $modelId = 'gemini-1.5-pro-vision', bool $includeHistory = false)`**
    * Initializes a new `GeminiClient` instance.
    * `$apiKey`: Your Google Gemini API key.
    * `$modelId`: The Gemini model ID to use (optional, defaults to `gemini-1.5-pro-vision`).
    * `$includeHistory`: Whether to automatically include conversation history in subsequent `sendPrompt` calls (optional, defaults to `false`).
* **`sendPrompt(array|string $prompts, string $responseType = self::RESPONSE_TYPE_TEXT): mixed`**
    * Sends one or more prompts to the Gemini API.
    * `$prompts`: A single string (for a single text prompt) or an array of prompts. Each element can be:
        * A string: For a text prompt (e.g., `"What is the weather like?"`).
        * An associative array for text: `['type' => 'text', 'text' => 'your text']` (e.g., `['type' => 'text', 'text' => 'Explain photosynthesis.']`).
        * An associative array for a file (image or PDF) via path: `['type' => 'file', 'path' => '/path/to/file']` (e.g., `['type' => 'file', 'path' => '/path/to/image.jpg']`).
        * An associative array for a file (image or PDF) via base64 encoded data:
            * `['type' => 'file', 'base64' => 'base64_data', 'mimeType' => 'image/*|application/pdf']` (e.g., `['type' => 'file', 'base64' => 'iVBORw0KGgo...', 'mimeType' => 'image/png']`). **Required if the base64 string does not have a `data:` URL prefix.**
            * `['type' => 'file', 'base64' => 'data:mime/type;base64,base64_data']` (e.g., `['type' => 'file', 'base64' => 'data:image/png;base64,iVBORw0KGgo...']`). **The MIME type will be automatically detected from the prefix if it is valid.**
        * An associative array for a file (image or PDF) via URL:
            * `['type' => 'file', 'url' => 'https://example.com/path/to/file']` (e.g., `['type' => 'file', 'url' => 'https://example.com/image.jpg']`).
    * `$responseType`: The desired format of the response (`GeminiClient::RESPONSE_TYPE_TEXT` or `GeminiClient::RESPONSE_TYPE_PHP_OBJECT`). Defaults to `GeminiClient::RESPONSE_TYPE_TEXT`.
    * Returns the API response in the specified format or `null` on error.
* **`getHistory(): array`**
    * Returns the current conversation history as an array of messages (each message is an associative array with `role` and `text` or `inlineData`).
* **`clearHistory(): void`**
    * Clears the current conversation history.

### Constants

* `GeminiClient::RESPONSE_TYPE_TEXT`: Constant for requesting the response as plain text.
* `GeminiClient::RESPONSE_TYPE_PHP_OBJECT`: Constant for requesting the response as a PHP object (decoded JSON).

## Error Handling

The `sendPrompt` method can throw a `GeminiApiException` (a custom exception class extending `Exception`) in several scenarios:

* **cURL Errors:** If there are issues with the HTTP request itself (e.g., network connectivity problems, SSL certificate issues), a `GeminiApiException` will be thrown with a message describing the cURL error.
* **HTTP Errors:** If the Gemini API returns a non-200 HTTP status code (indicating an error at the API level), a `GeminiApiException` will be thrown. The exception message will include the HTTP status code and the raw error response from the API.
* **Gemini API Errors (within JSON response):** Even with a successful HTTP status code, the Gemini API might return an error within the JSON response (e.g., invalid API key, model not found, quota exceeded, content safety violations). The `sendPrompt` method parses the JSON response and will throw a `GeminiApiException` if an `error` key is present in the response. The exception message will include the specific error message and code provided by the Gemini API.
* **JSON Decoding Errors:** If there is an issue decoding the JSON response from the API (e.g., malformed JSON), a `GeminiApiException` will be thrown with a message describing the decoding error and the raw response.
* **Invalid Input:** The `sendPrompt` method performs basic validation on the input prompts (e.g., missing MIME type for Base64 data without a prefix, unsupported MIME types). If invalid input is detected, a `GeminiApiException` will be thrown with a descriptive message.
* **File Not Found:** When providing image or PDF via file path, if the specified file does not exist, a `GeminiApiException` will be thrown.

It is crucial to wrap your `sendPrompt` calls in a `try...catch` block to gracefully handle these potential errors and provide informative feedback to your users or log the errors for debugging.

```php
try {
    $result = $geminiClient->sendPrompt("This is a bad prompt.");
    echo $result;
} catch (GeminiApiException $e) {
    echo "Gemini API Error: " . $e->getMessage() . "\n";
    // You might want to log the error details for debugging:
    // error_log("Gemini API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    // Handle other potential PHP exceptions
}
```

## Contributing

Contributing to this library is **not permitted** under the proprietary license.

## Limitations

* Streaming responses from the Gemini API are not currently supported.
* Adherence to the Gemini API's rate limits and media size/format restrictions is the responsibility of the user.

## License

The source code in this repository is proprietary and owned by [Shures Arwasyi](mailto:shuresarwasyi@gmail.com). It is protected by intellectual property laws and may not be used, copied, or distributed without explicit permission from owner.

All inquiries regarding licensing and usage should be directed to the creator.