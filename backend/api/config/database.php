<?php
/**
 * Database Connection Class
 * St. Lawrence Junior School - Kabowa
 * 
 * This class handles the database connection using PDO
 * PHP Version: 8.5.1
 */

require_once __DIR__ . '/env.php';

class Database {
    // Database credentials from environment variables
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $charset = "utf8mb4";
    
    // PDO connection object
    public $conn;
    
    public function __construct() {
        $this->host = env('DB_HOST', env('MYSQLHOST', 'localhost'));
        $this->port = env('DB_PORT', env('MYSQLPORT', '3306'));
        $this->db_name = env('DB_NAME', env('MYSQLDATABASE', 'st_lawrence_school'));
        $this->username = env('DB_USER', env('MYSQLUSER', 'root'));
        $this->password = env('DB_PASS', env('MYSQLPASSWORD', ''));
    }
    
    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Create DSN (Data Source Name)
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            // PDO options for better security and error handling
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
                PDO::ATTR_PERSISTENT         => false                     // Don't use persistent connections
            ];
            
            // Create PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // Log error and return null
            error_log("Database Connection Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please try again later.'
            ]);
            exit();
        }
        
        return $this->conn;
    }
}
?>
