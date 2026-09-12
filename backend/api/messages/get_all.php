<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all contact submissions including reply info
    $stmt = $db->prepare("
        SELECT 
            cs.id,
            cs.name,
            cs.email,
            cs.phone,
            cs.subject,
            cs.message,
            cs.status,
            cs.submitted_date,
            cs.replied_by,
            cs.reply_date,
            cs.reply_message,
            u.full_name AS replied_by_name
        FROM contact_submissions cs
        LEFT JOIN users u ON cs.replied_by = u.id
        ORDER BY cs.submitted_date DESC
    ");
    $stmt->execute();
    $rawMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $messages = array_map(function($msg) {
        $msg['is_read'] = ($msg['status'] !== 'new') ? 1 : 0;
        return $msg;
    }, $rawMessages);
    
    echo json_encode([
        'success' => true,
        'data' => $messages
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
