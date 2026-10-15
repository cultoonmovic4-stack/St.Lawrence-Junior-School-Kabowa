<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';
require_once '../helpers/TestimonialPhotoStore.php';

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
    
    // Photo is stored in the database so it survives container redeploys
    $photoBin = null;
    $photoMime = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        [$photoBin, $photoMime] = TestimonialPhotoStore::prepare($_FILES['photo']);
    }
    $photo_url = null;
    
    $database = new Database();
    $db = $database->getConnection();
    TestimonialPhotoStore::ensureSchema($db);
    
    // Insert testimonial
    $stmt = $db->prepare("
        INSERT INTO testimonials 
        (parent_name, parent_role, testimonial_text, rating, photo_url, photo_data, photo_mime, status, created_at) 
        VALUES 
        (:parent_name, :parent_role, :testimonial_text, :rating, :photo_url, :photo_data, :photo_mime, 'approved', NOW())
    ");
    
    $stmt->bindParam(':parent_name', $parent_name);
    $stmt->bindParam(':parent_role', $testimonial_type);
    $stmt->bindParam(':testimonial_text', $testimonial_text);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':photo_url', $photo_url);
    $stmt->bindParam(':photo_data', $photoBin, PDO::PARAM_LOB);
    $stmt->bindParam(':photo_mime', $photoMime);
    
    if ($stmt->execute()) {
        $testimonial_id = $db->lastInsertId();
        
        if ($photoBin !== null) {
            $photo_url = TestimonialPhotoStore::photoUrlFor((int) $testimonial_id);
            $upd = $db->prepare("UPDATE testimonials SET photo_url = :u WHERE id = :id");
            $upd->execute([':u' => $photo_url, ':id' => $testimonial_id]);
        }
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
