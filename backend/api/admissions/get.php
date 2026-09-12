<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

requirePermission('admission.view');

try {
    if (empty($_GET['id'])) {
        throw new Exception('Application ID is required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM admission_applications WHERE id = :id
    ");
    
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $application
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
