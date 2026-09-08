<?php
// Disable all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any errors
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';

// Clear any output that might have been generated
if (ob_get_length()) ob_clean();

// Check authentication
$auth = check_auth();
if (!$auth['authenticated']) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    ob_end_flush();
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    
    // Get upcoming events
    $stmt = $db->prepare("
        SELECT 
            id,
            title,
            description,
            event_date,
            event_time,
            location,
            category,
            status,
            image_url
        FROM events
        WHERE status IN ('upcoming', 'ongoing') OR event_date >= CURDATE()
        ORDER BY event_date ASC, event_time ASC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format events
    $formattedEvents = array_map(function($event) {
        $eventDate = new DateTime($event['event_date']);
        $timeFormatted = !empty($event['event_time']) ? date('g:i A', strtotime($event['event_time'])) : 'All Day';
        
        return [
            'id' => $event['id'],
            'title' => $event['title'],
            'description' => $event['description'],
            'date' => $event['event_date'],
            'day' => $eventDate->format('d'),
            'month' => strtoupper($eventDate->format('M')),
            'start_time' => $timeFormatted,
            'end_time' => '',
            'time_range' => $timeFormatted,
            'location' => $event['location'] ?? 'School Campus',
            'category' => $event['category'] ?? 'academic',
            'status' => $event['status']
        ];
    }, $events);
    
    // Clear any output buffer before sending JSON
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => true,
        'data' => $formattedEvents
    ]);
    
    ob_end_flush();
    
} catch (PDOException $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
