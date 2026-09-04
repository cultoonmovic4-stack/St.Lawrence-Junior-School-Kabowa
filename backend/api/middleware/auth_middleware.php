<?php
/**
 * Authentication Middleware
 * Protects API endpoints by checking if user is logged in
 * Include this file at the top of any protected API endpoint
 */

require_once __DIR__ . '/../helpers/SessionHelper.php';

// Initialize hardened session
SessionHelper::start();

/**
 * Compatibility helper for endpoints expecting check_auth()
 * @return array
 */
function check_auth() {
    return [
        'authenticated' => isAuthenticated(),
        'user' => getCurrentUser()
    ];
}

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require authentication - exits if not logged in
 */
function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please login.'
        ]);
        exit();
    }
}

/**
 * Check if user has specific role level or higher
 * @param int $requiredLevel - Minimum role level required
 * @return bool
 */
function hasRoleLevel($requiredLevel) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userLevel = $_SESSION['role_level'] ?? 0;
    return $userLevel >= $requiredLevel;
}

/**
 * Require specific role level - exits if insufficient permissions
 * @param int $requiredLevel - Minimum role level required
 */
function requireRoleLevel($requiredLevel) {
    requireAuth();
    
    if (!hasRoleLevel($requiredLevel)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient permissions. Access denied.'
        ]);
        exit();
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role_name' => $_SESSION['role_name'] ?? null,
        'role_level' => $_SESSION['role_level'] ?? null
    ];
}
?>
