<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';

    $sql = "SELECT id, parent_name, parent_role, testimonial_text, rating, photo_url, status, display_order, created_at 
            FROM testimonials WHERE 1=1";
    $params = [];

    if (!empty($role)) {
        $sql .= " AND parent_role = :role";
        $params[':role'] = $role;
    }
    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $testimonials
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
