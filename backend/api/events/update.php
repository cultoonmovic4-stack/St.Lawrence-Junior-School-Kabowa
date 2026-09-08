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
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        exit;
    }

    $required = ['event_title', 'event_date', 'start_time', 'location', 'category'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit;
        }
    }

    $database = new Database();
    $db = $database->getConnection();

    $eventTime = $data['start_time'];
    $description = $data['event_description'] ?? '';
    if (!empty($data['end_time']) && strpos($description, 'Time:') === false) {
        $description .= "\nTime: " . $data['start_time'] . " - " . $data['end_time'];
    }

    $stmt = $db->prepare("
        UPDATE events 
        SET title = :title,
            description = :description,
            event_date = :event_date,
            event_time = :event_time,
            location = :location,
            category = :category,
            status = :status
        WHERE id = :id
    ");

    $status = $data['status'] ?? 'upcoming';

    $stmt->bindParam(':title', $data['event_title']);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':event_date', $data['event_date']);
    $stmt->bindParam(':event_time', $eventTime);
    $stmt->bindParam(':location', $data['location']);
    $stmt->bindParam(':category', $data['category']);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } else {
        throw new Exception('Failed to update event');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
