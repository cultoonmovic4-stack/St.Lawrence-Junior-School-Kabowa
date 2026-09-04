<?php
/**
 * Login API Endpoint (MySQLi Version)
 * Handles user authentication
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Include database connection
require_once '../config/Database_MySQLi.php';

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
$email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
$password = $data->password;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit();
}

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Prepare SQL query
    $query = "SELECT u.id, u.username, u.email, u.password_hash, u.full_name, u.phone, u.status, 
                     r.id as role_id, r.role_name, r.role_level
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.id
              WHERE u.email = ?
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
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
            // Password is correct - start session
            session_start();
            
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['role_level'] = $user['role_level'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $user['id']);
            $updateStmt->execute();
            
            // Log activity
            $logQuery = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                         VALUES (?, 'login', 'User logged in', ?, ?)";
            $logStmt = $conn->prepare($logQuery);
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $logStmt->bind_param('iss', $user['id'], $ip, $user_agent);
            $logStmt->execute();
            
            // Return success response
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'phone' => $user['phone'],
                    'role_name' => $user['role_name'],
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
    
    // Close connections
    $stmt->close();
    $database->closeConnection();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.',
        'error' => $e->getMessage()
    ]);
}
?>
