<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// No authentication - just test the database queries
try {
    require_once '../config/Database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    // Get teachers count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM teachers");
    $stmt->execute();
    $stats['total_teachers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get contact submissions
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contact_submissions WHERE status = 'new'");
    $stmt->execute();
    $stats['unread_messages'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get admission applications
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM admission_applications");
    $stmt->execute();
    $stats['admissions_count'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get events count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM events");
    $stmt->execute();
    $stats['events_count'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'debug' => 'No auth API working'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>