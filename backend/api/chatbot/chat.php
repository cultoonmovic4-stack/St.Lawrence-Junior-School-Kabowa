<?php
/**
 * St. Lawrence School AI Assistant - Chat API
 * Handles chat requests with session context memory, rate limiting, and conversational matching
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'knowledge_base.php';

// Start session for conversation tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// RATE LIMITING: Maximum 30 requests per minute per session
// ---------------------------------------------------------
$currentTime = time();
if (!isset($_SESSION['rate_limiter'])) {
    $_SESSION['rate_limiter'] = [
        'window_start' => $currentTime,
        'count' => 0
    ];
}

// Reset rate limiter window every 60 seconds
if ($currentTime - $_SESSION['rate_limiter']['window_start'] > 60) {
    $_SESSION['rate_limiter'] = [
        'window_start' => $currentTime,
        'count' => 0
    ];
}

$_SESSION['rate_limiter']['count']++;

if ($_SESSION['rate_limiter']['count'] > 30) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Rate limit exceeded. Please wait a moment before sending another message.',
        'response' => "You are sending messages very quickly! Please wait a moment before trying again."
    ]);
    exit();
}

// Initialize knowledge base
$kb = new KnowledgeBase();

// Initialize context and history if not set
if (!isset($_SESSION['chat_context']) || !is_array($_SESSION['chat_context'])) {
    $_SESSION['chat_context'] = [
        'active_topic' => 'general',
        'last_category' => null,
        'turn_count' => 0
    ];
}

if (!isset($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Get request data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? 'chat';

// Handle different actions
switch ($action) {
    case 'init':
        // Reset chat session state
        $_SESSION['chat_history'] = [];
        $_SESSION['chat_started'] = time();
        $_SESSION['chat_context'] = [
            'active_topic' => 'general',
            'last_category' => null,
            'turn_count' => 0
        ];
        
        echo json_encode([
            'success' => true,
            'message' => "Hello! 👋 Welcome to St. Lawrence Junior School - Kabowa. I am your virtual school assistant.\n\nHow can I help you today? You can ask about our **admissions**, **school fees**, **nursery & primary programmes**, or **boarding facilities**.",
            'quickActions' => $kb->getQuickActions(),
            'sessionId' => session_id()
        ]);
        break;
        
    case 'chat':
    case 'quickAction':
        $question = trim($input['message'] ?? ($input['question'] ?? ''));
        
        if (empty($question)) {
            echo json_encode([
                'success' => false,
                'error' => 'No message provided'
            ]);
            exit();
        }
        
        // Input sanitization: limit length to 500 characters
        if (mb_strlen($question) > 500) {
            $question = mb_substr($question, 0, 500);
        }
        
        // Find answer from knowledge base using session context
        $currentContext = $_SESSION['chat_context'] ?? [];
        $result = $kb->findAnswer($question, $currentContext);
        
        // Update session context
        $_SESSION['chat_context']['active_topic'] = $result['active_topic'] ?? 'general';
        $_SESSION['chat_context']['last_category'] = $result['category'] ?? null;
        if (isset($result['active_class'])) {
            $_SESSION['chat_context']['active_class'] = $result['active_class'];
        }
        $_SESSION['chat_context']['turn_count'] = ($_SESSION['chat_context']['turn_count'] ?? 0) + 1;
        
        // Store in chat history
        $_SESSION['chat_history'][] = [
            'user' => $question,
            'bot' => $result['response'],
            'timestamp' => time(),
            'category' => $result['category']
        ];
        
        // Cap history to last 12 items to prevent memory bloat
        if (count($_SESSION['chat_history']) > 12) {
            $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -12);
        }
        
        echo json_encode([
            'success' => true,
            'response' => $result['response'],
            'found' => $result['found'],
            'category' => $result['category'],
            'active_topic' => $result['active_topic'] ?? 'general',
            'suggestions' => $result['suggestions'] ?? $kb->getQuickActions(),
            'timestamp' => date('H:i')
        ]);
        break;
        
    case 'history':
        $history = $_SESSION['chat_history'] ?? [];
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        break;
        
    case 'clear':
        $_SESSION['chat_history'] = [];
        $_SESSION['chat_context'] = [
            'active_topic' => 'general',
            'last_category' => null,
            'turn_count' => 0
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Chat history cleared'
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}
