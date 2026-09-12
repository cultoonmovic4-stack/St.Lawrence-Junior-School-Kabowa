<?php
/**
 * St. Lawrence Junior School Kabowa
 * Legacy Check Email Endpoint - Hardened for Security
 * Redirects to secure status lookup with reference + phone
 */

header('Content-Type: application/json');

http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Unauthenticated email querying is disabled for privacy protection. Please check status using your Application Reference and Registered Telephone Number via check-status.php'
]);
exit;
