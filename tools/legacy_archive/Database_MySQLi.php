<?php
/**
 * Database Connection Class (MySQLi Version)
 * St. Lawrence Junior School - Kabowa
 * 
 * This class handles the database connection using MySQLi
 * PHP Version: 8.5.1
 */

class Database {
    // Database credentials
    private $host = "localhost";
    private $db_name = "st_lawrence_school";
    private $username = "root";
    private $password = "";
    
    // MySQLi connection object
    public $conn;
    
    /**
     * Get database connection
     * @return mysqli|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Create MySQLi connection
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset to utf8mb4
            $this->conn->set_charset("utf8mb4");
            
        } catch(Exception $e) {
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
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
