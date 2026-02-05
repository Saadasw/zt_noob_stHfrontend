<?php
/**
 * AI Chat API Endpoint
 * 
 * Handles chat requests from the Reports pages.
 * Validates session, collects context, and calls Gemini API.
 */

// Start session and include dependencies
session_start();

// Set JSON response headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Include required files
require_once '../config/db_connect.php';
require_once '../config/gemini_config.php';

/**
 * Send JSON response and exit
 */
function sendResponse($success, $data = null, $error = null, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'response' => $data,
        'error' => $error
    ]);
    exit;
}

/**
 * Validate user session and role
 */
function validateSession()
{
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Unauthorized. Please log in.', 401);
    }

    $allowedRoles = ['admin', 'branch_admin'];
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
        sendResponse(false, null, 'Access denied. This feature is only available to administrators.', 403);
    }

    return true;
}

/**
 * Call Gemini API with the constructed prompt
 */
function callGeminiAPI($prompt)
{
    // Check if API key is configured
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_API_KEY_HERE') {
        sendResponse(false, null, 'AI feature not configured. Please add your Gemini API key in config/gemini_config.php', 500);
    }

    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => GEMINI_MAX_TOKENS,
            'temperature' => 0.7
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => GEMINI_TIMEOUT,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        // Disable SSL verification for local development (XAMPP issue)
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Handle cURL errors
    if ($curlError) {
        error_log("Gemini API cURL error: " . $curlError);
        sendResponse(false, null, 'Connection error: ' . $curlError, 500);
    }

    // Parse response
    $responseData = json_decode($response, true);

    // Handle API errors
    if ($httpCode !== 200) {
        $errorMessage = $responseData['error']['message'] ?? 'Unknown API error';
        error_log("Gemini API error (HTTP $httpCode): " . $errorMessage);

        if ($httpCode === 429) {
            sendResponse(false, null, 'Too many requests. Please wait a moment and try again.', 429);
        }

        if ($httpCode === 400) {
            sendResponse(false, null, 'API Error: ' . $errorMessage, 400);
        }

        if ($httpCode === 403) {
            sendResponse(false, null, 'API Key invalid or not authorized. Please check your Gemini API key.', 403);
        }

        sendResponse(false, null, 'AI service error (HTTP ' . $httpCode . '): ' . $errorMessage, 500);
    }

    // Extract text from response
    $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text) {
        sendResponse(false, null, 'Empty response from AI. Please try rephrasing your question.', 500);
    }

    return $text;
}

/**
 * Build the system prompt with context
 */
function buildPrompt($question, $context, $userRole, $branchName = null)
{
    $roleDescription = $userRole === 'admin'
        ? 'a System Administrator viewing organization-wide reports'
        : 'a Branch Administrator viewing branch-specific reports';

    $branchContext = $branchName ? " for {$branchName}" : '';

    $systemPrompt = <<<PROMPT
You are an AI assistant for St. George Hospital's management system. You are helping {$roleDescription}{$branchContext}.

Your role is to:
1. Answer questions about the hospital's report data shown below
2. Provide insights and analysis based on the numbers
3. Be concise and professional
4. Only use the data provided - don't make up numbers
5. If asked something not in the data, politely say you don't have that information

CURRENT REPORT DATA:
{$context}

USER QUESTION: {$question}

Please provide a helpful, concise response based on the data above.
PROMPT;

    return $systemPrompt;
}

/**
 * Format context data for the prompt
 */
function formatContext($contextData)
{
    if (empty($contextData)) {
        return "No specific report data available.";
    }

    $formatted = "";

    foreach ($contextData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));

        if (is_array($value)) {
            $formatted .= "\n{$label}:\n";
            foreach ($value as $item) {
                if (is_array($item)) {
                    $formatted .= "  - " . implode(': ', $item) . "\n";
                } else {
                    $formatted .= "  - {$item}\n";
                }
            }
        } else {
            $formatted .= "{$label}: {$value}\n";
        }
    }

    return $formatted;
}

// ===========================================
// MAIN REQUEST HANDLING
// ===========================================

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Method not allowed. Use POST.', 405);
}

// Validate session
validateSession();

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse(false, null, 'Invalid request body. Expected JSON.', 400);
}

// Validate required fields
$question = trim($input['question'] ?? '');
$context = $input['context'] ?? [];
$branchName = $input['branch_name'] ?? null;

if (empty($question)) {
    sendResponse(false, null, 'Please enter a question.', 400);
}

if (strlen($question) > 500) {
    sendResponse(false, null, 'Question is too long. Please keep it under 500 characters.', 400);
}

// Get user role from session
$userRole = $_SESSION['user_role'];

// Format context and build prompt
$formattedContext = formatContext($context);
$prompt = buildPrompt($question, $formattedContext, $userRole, $branchName);

// Call Gemini API
$aiResponse = callGeminiAPI($prompt);

// Return successful response
sendResponse(true, $aiResponse);
?>