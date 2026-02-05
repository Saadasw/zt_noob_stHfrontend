<?php
/**
 * Gemini API Configuration
 * 
 * Get your free API key from: https://makersuite.google.com/app/apikey
 * Free tier: 15 requests per minute
 * 
 * IMPORTANT: Set your API key in the .env file, not here!
 * Create a .env file in the project root with:
 * GEMINI_API_KEY=your_api_key_here
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Gemini API Key (loaded from .env)
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// Gemini API endpoint (using gemini-flash-latest for fast responses)
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent');

// Maximum tokens in response
define('GEMINI_MAX_TOKENS', 1024);

// Request timeout in seconds
define('GEMINI_TIMEOUT', 30);

// Validate API key is set
if (empty(GEMINI_API_KEY)) {
    error_log('WARNING: GEMINI_API_KEY is not set in .env file');
}
?>