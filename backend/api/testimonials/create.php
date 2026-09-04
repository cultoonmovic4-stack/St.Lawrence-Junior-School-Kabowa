<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get form data
    $parent_name = $_POST['parent_name'] ?? '';
    $testimonial_type = $_POST['testimonial_type'] ?? '';
    $testimonial_text = $_POST['testimonial_text'] ?? '';
    $rating = $_POST['rating'] ?? '';
    
    // Validate required fields
    if (empty($parent_name) || empty($testimonial_type) || empty($testimonial_text) || empty($rating)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be filled'
        ]);
        exit;
    }
    
    // Handle file upload securely
    $photo_url = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../../img/testimonials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['photo'],
            UploadSecurityHelper::CATEGORY_IMAGE,
            $upload_dir,
            'testimonial'
        );
        $photo_url = 'img/testimonials/' . $uploadResult['filename'];
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Insert testimonial
    $stmt = $db->prepare("
        INSERT INTO testimonials 
        (parent_name, parent_role, testimonial_text, rating, photo_url, status, created_at) 
        VALUES 
        (:parent_name, :parent_role, :testimonial_text, :rating, :photo_url, 'approved', NOW())
    ");
    
    $stmt->bindParam(':parent_name', $parent_name);
    $stmt->bindParam(':parent_role', $testimonial_type);
    $stmt->bindParam(':testimonial_text', $testimonial_text);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':photo_url', $photo_url);
    
    if ($stmt->execute()) {
        $testimonial_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Testimonial added successfully',
            'testimonial_id' => $testimonial_id,
            'photo_url' => $photo_url
        ]);
    } else {
        throw new Exception('Failed to add testimonial');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
