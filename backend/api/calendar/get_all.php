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

    $types = $_GET['types'] ?? ''; // e.g. "important_days" or "academic" or specific event_type

    $sql = "SELECT id, event_title, event_description, start_date, end_date, event_type, all_day, color, created_at 
            FROM calendar_events WHERE 1=1";
    $params = [];

    if ($types === 'important_days') {
        $sql .= " AND event_type IN ('holiday', 'activity', 'other')";
    } elseif ($types === 'academic') {
        $sql .= " AND event_type IN ('exam', 'meeting')";
    } elseif (!empty($types)) {
        $sql .= " AND event_type = :event_type";
        $params[':event_type'] = $types;
    }

    $sql .= " ORDER BY start_date DESC";

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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
