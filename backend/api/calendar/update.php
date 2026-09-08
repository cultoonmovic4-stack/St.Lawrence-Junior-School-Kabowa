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

    if (!$id || empty($data['event_title']) || empty($data['start_date']) || empty($data['event_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields: id, event_title, start_date, event_type']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        UPDATE calendar_events 
        SET event_title = :title,
            event_description = :description,
            start_date = :start_date,
            end_date = :end_date,
            event_type = :event_type
        WHERE id = :id
    ");

    $endDate = !empty($data['end_date']) ? $data['end_date'] : $data['start_date'];
    $description = $data['event_description'] ?? '';

    $stmt->bindParam(':title', $data['event_title']);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':start_date', $data['start_date']);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->bindParam(':event_type', $data['event_type']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Calendar event updated successfully']);
    } else {
        throw new Exception('Failed to update calendar event');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
