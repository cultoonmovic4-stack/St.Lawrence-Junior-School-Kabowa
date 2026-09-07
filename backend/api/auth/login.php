<?php
/**
 * Login API Endpoint
 * Handles user authentication
 */

// NOTE: display_errors disabled for security — error details must not leak to clients
error_reporting(0);
ini_set('display_errors', '0');

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Include database connection and session helper
require_once '../config/Database.php';
require_once '../helpers/SessionHelper.php';

// Initialize hardened session before any auth logic
SessionHelper::start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit();
}

// Sanitize input
$login = trim($data->email);
$password = $data->password;


try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check which password column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password%'");
    $columns = $checkColumn->fetchAll(PDO::FETCH_COLUMN);
    $passwordColumn = in_array('password', $columns) ? 'password' : 'password_hash';
    
    // Prepare SQL query
    $query = "SELECT u.id, u.username, u.email, u.{$passwordColumn} as password_hash, u.full_name, u.phone, u.status, 
                     r.id as role_id, r.role_name, r.role_level
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.id
              WHERE (u.email = :login_email OR u.username = :login_user)
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':login_email', $login);
    $stmt->bindParam(':login_user', $login);
    $stmt->execute();
    
    // Check if user exists
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ]);
            exit();
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Session is already started by SessionHelper::start() above.
            // Regenerate session ID now — BEFORE writing any auth state —
            // to prevent session fixation attacks.
            SessionHelper::regenerate();
            
            // Store user data in session (only non-sensitive identity data)
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['role_id']    = $user['role_id'];
            $_SESSION['role_name']  = $user['role_name'];
            $_SESSION['role_level'] = $user['role_level'];
            $_SESSION['logged_in']  = true;
            $_SESSION['login_method'] = 'password';
            $_SESSION['login_time'] = time();
            
            // Update last login timestamp
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();
            
            // Log activity
            $logQuery = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                         VALUES (:user_id, 'login', 'User logged in', :ip, :user_agent)";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bindParam(':user_id', $user['id']);
            $logStmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
            $logStmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            $logStmt->execute();
            
            // Return success response — do NOT include password_hash or sensitive fields
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user_id'    => $user['id'],
                    'username'   => $user['username'],
                    'email'      => $user['email'],
                    'full_name'  => $user['full_name'],
                    'phone'      => $user['phone'],
                    'role_name'  => $user['role_name'],
                    'role_level' => $user['role_level']
                ]
            ]);
            
        } else {
            // Invalid password
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email or password'
            ]);
        }
        
    } else {
        // User not found
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.',
        'error' => $e->getMessage()
    ]);
}
?>
