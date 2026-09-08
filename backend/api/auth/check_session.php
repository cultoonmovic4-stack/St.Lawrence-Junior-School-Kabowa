<?php
/**
 * Check Session API Endpoint
 * St. Lawrence Junior School
 *
 * Verifies if a valid authenticated session exists.
 * Session is initialized with hardened cookie parameters via SessionHelper.
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../helpers/SessionHelper.php';

// Initialize hardened session (also checks/enforces idle timeout)
SessionHelper::start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $userData = [
        'user_id'    => $_SESSION['user_id']    ?? null,
        'username'   => $_SESSION['username']   ?? null,
        'email'      => $_SESSION['email']      ?? null,
        'full_name'  => $_SESSION['full_name']  ?? null,
        'role_name'  => $_SESSION['role_name']  ?? null,
        'role_level' => $_SESSION['role_level'] ?? null
    ];
    http_response_code(200);
    echo json_encode([
        'success'       => true,
        'logged_in'     => true,
        'authenticated' => true,
        'data'          => $userData,
        'user'          => $userData
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success'       => false,
        'logged_in'     => false,
        'authenticated' => false,
        'message'       => 'No active session'
    ]);
}
