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

    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';

    $sql = "SELECT id, title, description, event_date, event_time, location, category, status, image_url, created_at 
            FROM events WHERE 1=1";
    $params = [];

    if (!empty($category)) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }
    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY event_date DESC, event_time DESC";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $events
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
