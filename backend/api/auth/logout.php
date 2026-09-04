<?php
/**
 * Logout API Endpoint
 * St. Lawrence Junior School
 *
 * SECURITY: Uses SessionHelper::destroy() which:
 *   - Clears all $_SESSION data
 *   - Destroys the server-side session record
 *   - Expires the session cookie on the client (prevents cookie replay)
 * Works for both password and Google OAuth sessions.
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../config/Database.php';

// Initialize session with secure parameters before reading it
SessionHelper::start();

// Capture user ID before destroying session (needed for activity log)
$userId = $_SESSION['user_id'] ?? null;

// Attempt to log logout activity before destroying session
if ($userId !== null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $logStmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (:uid, 'logout', 'User logged out', :ip, :ua)
        ");
        $logStmt->execute([
            ':uid' => $userId,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Logging failure must never prevent logout completing
    }
}

// Fully destroy session: clears $_SESSION, server-side record, and client cookie
SessionHelper::destroy();

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Logout successful'
]);
