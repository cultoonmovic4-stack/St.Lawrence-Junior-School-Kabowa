<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';
require_once '../helpers/TestimonialPhotoStore.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $parent_name = $_POST['parent_name'] ?? '';
    $parent_role = $_POST['testimonial_type'] ?? $_POST['parent_role'] ?? '';
    $testimonial_text = $_POST['testimonial_text'] ?? '';
    $rating = $_POST['rating'] ?? 5;
    $status = $_POST['status'] ?? 'approved';

    if (!$id || empty($parent_name) || empty($testimonial_text)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and testimonial text are required']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    TestimonialPhotoStore::ensureSchema($db);

    // Check if new photo was uploaded (stored in the database, not on disk)
    $photo_url = null;
    $photoBin = null;
    $photoMime = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        [$photoBin, $photoMime] = TestimonialPhotoStore::prepare($_FILES['photo']);
        $photo_url = TestimonialPhotoStore::photoUrlFor((int) $id);
    }

    if ($photo_url) {
        $stmt = $db->prepare("
            UPDATE testimonials 
            SET parent_name = :name,
                parent_role = :role,
                testimonial_text = :text,
                rating = :rating,
                status = :status,
                photo_url = :photo,
                photo_data = :pdata,
                photo_mime = :pmime
            WHERE id = :id
        ");
        $stmt->bindParam(':photo', $photo_url);
        $stmt->bindParam(':pdata', $photoBin, PDO::PARAM_LOB);
        $stmt->bindParam(':pmime', $photoMime);
    } else {
        $stmt = $db->prepare("
            UPDATE testimonials 
            SET parent_name = :name,
                parent_role = :role,
                testimonial_text = :text,
                rating = :rating,
                status = :status
            WHERE id = :id
        ");
    }

    $stmt->bindParam(':name', $parent_name);
    $stmt->bindParam(':role', $parent_role);
    $stmt->bindParam(':text', $testimonial_text);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Testimonial updated successfully']);
    } else {
        throw new Exception('Failed to update testimonial');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
