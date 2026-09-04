<?php
/**
 * Google OAuth Login Handler
 * St. Lawrence Junior School
 *
 * SECURITY: Cryptographically verifies Google ID tokens via RS256 + JWKS.
 * Uses SessionHelper for session fixation prevention.
 */

// Suppress PHP error display — never expose internals to clients
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/GoogleAuthHelper.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';

// Load environment variables (provides GOOGLE_CLIENT_ID)
try {
    loadEnv();
} catch (Exception $e) {
    // Tolerate missing .env in local dev; client ID will fall back below
}

// Initialize hardened session before any auth logic
SessionHelper::start();

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Read raw credential from request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['credential'])) {
        throw new Exception('No credential provided');
    }

    // Retrieve the expected audience (our Google Client ID) from environment
    $expectedAudience = getenv('GOOGLE_CLIENT_ID')
        ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? null);

    if (empty($expectedAudience)) {
        // Hard-code fallback only as last resort — prefer .env
        $expectedAudience = '208073950289-13ga4hicat50qqg7bt1e3saie0r95d8i.apps.googleusercontent.com';
    }

    // -----------------------------------------------------------------------
    // CRITICAL: Cryptographic RS256 JWT verification
    // GoogleAuthHelper fetches Google's JWKS, validates the signature,
    // enforces algorithm (RS256 only, no "alg:none"), issuer, audience,
    // expiration, subject, email_verified — before ANY user data is trusted.
    // -----------------------------------------------------------------------
    $payload = GoogleAuthHelper::verifyIdToken($input['credential'], $expectedAudience);

    // At this point the token is cryptographically verified. Extract claims.
    $googleId = $payload['sub'];         // Verified Google user identifier
    $email    = $payload['email'];       // Trusted only after verification
    $name     = $payload['name']   ?? '';
    $picture  = $payload['picture'] ?? null;

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    // Look up existing user by email
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.full_name, u.status,
               u.google_id, u.profile_image,
               r.id as role_id, r.role_name, r.role_level
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.email = :email
        LIMIT 1
    ");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Google login is restricted to existing users only.
        // New accounts must be provisioned by an administrator.
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'No account found for this Google address. Contact the school administrator.'
        ]);
        exit();
    }

    // Enforce account active status
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been deactivated. Please contact administrator.'
        ]);
        exit();
    }

    // Link or refresh google_id / profile picture if not already stored
    if (empty($user['google_id']) || $user['google_id'] !== $googleId) {
        $updateStmt = $db->prepare("UPDATE users SET google_id = :gid WHERE id = :id");
        $updateStmt->bindParam(':gid', $googleId);
        $updateStmt->bindParam(':id', $user['id']);
        $updateStmt->execute();
    }

    // Update last login timestamp
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
       ->execute([':id' => $user['id']]);

    // Log login activity
    try {
        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (:uid, 'login', 'Google OAuth login', :ip, :ua)
        ");
        $logStmt->execute([
            ':uid' => $user['id'],
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Activity logging failure must never block authentication
    }

    // -------------------------------------------------------------------
    // Session Fixation Prevention:
    // Regenerate the session ID immediately after verification,
    // BEFORE writing any authentication state into the session.
    // -------------------------------------------------------------------
    SessionHelper::regenerate();

    // Establish authenticated session — mirror the same keys as password login
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['full_name']    = $user['full_name'];
    $_SESSION['role_id']      = $user['role_id'];
    $_SESSION['role_name']    = $user['role_name'];
    $_SESSION['role_level']   = $user['role_level'];
    $_SESSION['logged_in']    = true;
    $_SESSION['login_method'] = 'google';
    $_SESSION['login_time']   = time();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Google login successful',
        'data' => [
            'user_id'    => $user['id'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'full_name'  => $user['full_name'],
            'role_name'  => $user['role_name'],
            'role_level' => $user['role_level']
        ]
    ]);

} catch (Exception $e) {
    // Return a safe, generic error message — never expose JWT internals
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed. Please try again.'
    ]);
}
