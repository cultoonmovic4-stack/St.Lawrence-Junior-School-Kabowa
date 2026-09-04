<?php
// Direct MySQLi test without class
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$database = "st_lawrence_school";

// Try to connect
$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error,
        'mysqli_loaded' => extension_loaded('mysqli')
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'MySQLi connection successful!',
        'php_version' => PHP_VERSION,
        'mysqli_loaded' => extension_loaded('mysqli'),
        'server_info' => $conn->server_info
    ]);
    $conn->close();
}
?>
