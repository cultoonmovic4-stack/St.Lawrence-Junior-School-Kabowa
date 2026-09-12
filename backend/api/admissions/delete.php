<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';

// Check authentication and permission
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

requirePermission('admission.delete');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Application ID is required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get application to retrieve and securely delete signature file
    $stmt = $db->prepare("SELECT * FROM admission_applications WHERE id = :id");
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    // Safely delete signature PNG file if it exists
    if (!empty($application['signature_path'])) {
        $sigRelative = ltrim($application['signature_path'], '/\\');
        $cand1 = realpath(__DIR__ . '/../../../' . $sigRelative);
        $cand2 = realpath(__DIR__ . '/../../' . $sigRelative);
        $targetFile = ($cand1 && file_exists($cand1)) ? $cand1 : (($cand2 && file_exists($cand2)) ? $cand2 : null);
        if ($targetFile && is_file($targetFile)) {
            @unlink($targetFile);
        }
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM admission_applications WHERE id = :id");
    $stmt->bindParam(':id', $data['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Application deleted successfully!'
        ]);
    } else {
        throw new Exception('Failed to delete application');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
