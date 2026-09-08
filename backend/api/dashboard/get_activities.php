<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Collect activities from different tables
    $activities = [];
    
    // 1. Recent Admissions
    $stmt = $db->prepare("
        SELECT 
            'admission' as type,
            CONCAT('New admission application from ', student_first_name, ' ', student_last_name) as title,
            CONCAT('Applied for ', class_to_join) as description,
            submitted_date as activity_time
        FROM admission_applications
        ORDER BY submitted_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // 2. Recent Gallery Uploads
    $stmt = $db->prepare("
        SELECT 
            'gallery' as type,
            'Gallery image uploaded' as title,
            CONCAT(category, ' - ', title) as description,
            created_at as activity_time
        FROM gallery_images
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // 3. Recent Teachers Added
    $stmt = $db->prepare("
        SELECT 
            'teacher' as type,
            CONCAT('New teacher added: ', full_name) as title,
            CONCAT(department, ' - ', position) as description,
            created_at as activity_time
        FROM teachers
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // 4. Recent Library Resources
    $stmt = $db->prepare("
        SELECT 
            'library' as type,
            CONCAT('Library resource: ', title) as title,
            CONCAT(category, ' - ', download_count, ' downloads') as description,
            created_at as activity_time
        FROM library_resources
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // 5. Recent Contact Messages
    $stmt = $db->prepare("
        SELECT 
            'message' as type,
            CONCAT('New message from ', name) as title,
            subject as description,
            submitted_date as activity_time
        FROM contact_submissions
        ORDER BY submitted_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Sort all activities by time
    usort($activities, function($a, $b) {
        return strtotime($b['activity_time']) - strtotime($a['activity_time']);
    });
    
    // Limit to requested number
    $activities = array_slice($activities, 0, $limit);
    
    // Format time ago
    foreach ($activities as &$activity) {
        $activity['time_ago'] = getTimeAgo($activity['activity_time']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $activities
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

if (!function_exists('getTimeAgo')) {
    function getTimeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }
}
