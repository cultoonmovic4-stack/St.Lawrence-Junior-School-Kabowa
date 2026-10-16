<?php
/**
 * Universal CORS and Preflight (OPTIONS) Handler
 * St. Lawrence Junior School - Kabowa API
 */

// Allow credentials and handle Origin properly
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
$allowedOrigins = [
    'https://stlawrencejuniorschoolkabowa.com',
    'https://www.stlawrencejuniorschoolkabowa.com',
    'https://cultoonmovic4-stack.github.io',
    'http://localhost',
    'http://127.0.0.1'
];

$isAllowedOrigin = is_string($origin) && (
    in_array($origin, $allowedOrigins, true) ||
    str_ends_with($origin, '.stlawrencejuniorschoolkabowa.com') ||
    str_starts_with($origin, 'http://localhost') ||
    str_starts_with($origin, 'http://127.0.0.1')
);

if ($isAllowedOrigin) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Preflight OPTIONS early return
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}
