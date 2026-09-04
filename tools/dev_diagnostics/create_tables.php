<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';

// Check authentication
$auth = check_auth();
if (!$auth['authenticated']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/create_missing_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('SQL file not found');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $results = [];
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        try {
            $stmt = $db->prepare($statement);
            $stmt->execute();
            
            if (strpos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`(\w+)`/', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                $results[] = "Created table: $tableName";
            } elseif (strpos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO.*?`(\w+)`/', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                $results[] = "Inserted sample data into: $tableName";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results[] = "Table already exists (skipped)";
            } else {
                $results[] = "Error: " . $e->getMessage();
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tables created successfully',
        'results' => $results
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>