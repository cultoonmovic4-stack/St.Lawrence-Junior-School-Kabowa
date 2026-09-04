<?php
/**
 * Database Connection Test
 * Use this file to test if the database connection is working
 * 
 * Access: http://localhost/your-project/backend/api/config/test_connection.php
 */

// Include the Database class
require_once 'Database.php';

// Set header for JSON response
header('Content-Type: application/json');

// Create database instance
$database = new Database();

// Try to get connection
$conn = $database->getConnection();

// Check if connection is successful
if($conn) {
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful!',
        'database' => 'st_lawrence_school',
        'host' => 'localhost',
        'php_version' => phpversion()
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed!'
    ]);
}
?>
