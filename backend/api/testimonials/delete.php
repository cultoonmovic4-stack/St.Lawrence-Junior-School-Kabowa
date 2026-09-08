<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Testimonial ID is required']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Fetch existing photo to remove file if exists
    $fetchStmt = $db->prepare("SELECT photo_url FROM testimonials WHERE id = :id");
    $fetchStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $fetchStmt->execute();
    $photoUrl = $fetchStmt->fetchColumn();

    $stmt = $db->prepare("DELETE FROM testimonials WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($photoUrl && file_exists(__DIR__ . '/../../../' . $photoUrl)) {
            @unlink(__DIR__ . '/../../../' . $photoUrl);
        }
        echo json_encode(['success' => true, 'message' => 'Testimonial deleted successfully']);
    } else {
        throw new Exception('Failed to delete testimonial');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
