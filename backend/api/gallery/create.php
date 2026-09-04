<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Handle file upload securely
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed or no image provided');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/gallery/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadResult = UploadSecurityHelper::validateAndSave(
        $_FILES['image'],
        UploadSecurityHelper::CATEGORY_IMAGE,
        $uploadDir,
        'gallery'
    );
    
    // Auto-generate title from filename
    $originalName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
    $title = ucwords(str_replace(['_', '-'], ' ', $originalName));
    
    // Default category
    $category = 'events';
    $description = '';
    $upload_date = date('Y-m-d');
    
    // Save to database
    $database = new Database();
    $db = $database->getConnection();
    
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'];
    
    $stmt = $db->prepare("
        INSERT INTO gallery_images 
        (title, description, image_url, category, upload_date, uploaded_by, status) 
        VALUES 
        (:title, :description, :image_url, :category, :upload_date, :uploaded_by, 'active')
    ");
    
    $imageUrl = 'uploads/gallery/' . $uploadResult['filename'];
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':image_url', $imageUrl);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':upload_date', $upload_date);
    $stmt->bindParam(':uploaded_by', $userId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Gallery image uploaded successfully!',
            'data' => [
                'id' => $db->lastInsertId(),
                'image_url' => $imageUrl
            ]
        ]);
    } else {
        throw new Exception('Failed to save to database');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
