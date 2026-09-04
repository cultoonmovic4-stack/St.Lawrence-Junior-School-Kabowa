<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simple test without authentication to isolate the issue
try {
    require_once '../config/Database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    // Test teachers count
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM teachers");
        $stmt->execute();
        $stats['total_teachers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['teachers_error'] = $e->getMessage();
        $stats['total_teachers'] = 0;
    }
    
    // Test contact_submissions count
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM contact_submissions WHERE status = 'new'");
        $stmt->execute();
        $stats['unread_messages'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['messages_error'] = $e->getMessage();
        $stats['unread_messages'] = 0;
    }
    
    // Test admission_applications count
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM admission_applications");
        $stmt->execute();
        $stats['admissions_count'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['admissions_error'] = $e->getMessage();
        $stats['admissions_count'] = 0;
    }
    
    // Test events count
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM events");
        $stmt->execute();
        $stats['events_count'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['events_error'] = $e->getMessage();
        $stats['events_count'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'message' => 'Test API working'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>