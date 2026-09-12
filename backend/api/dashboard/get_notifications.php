<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Helper function to calculate time ago
if (!function_exists('getTimeAgo')) {
    function getTimeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $notifications = [];
    
    // Get recent pending admissions (last 10)
    $stmt = $db->prepare("
        SELECT 
            id,
            application_reference,
            student_surname,
            student_other_names,
            class_to_join,
            submitted_date
        FROM admission_applications 
        WHERE status = 'pending'
        ORDER BY submitted_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($admissions as $admission) {
        $timeAgo = getTimeAgo($admission['submitted_date']);
        $studentFullName = trim($admission['student_surname'] . ' ' . $admission['student_other_names']);
        $notifications[] = [
            'id' => 'admission_' . $admission['id'],
            'type' => 'admission',
            'title' => 'New Admission Application',
            'text' => $studentFullName . ' applied for ' . $admission['class_to_join'],
            'time' => $timeAgo,
            'timestamp' => $admission['submitted_date'],
            'unread' => true,
            'icon' => 'fa-user-graduate',
            'color' => 'blue',
            'data' => [
                'admission_id' => $admission['id'],
                'application_reference' => $admission['application_reference']
            ]
        ];
    }
    
    // Get recent unread contact messages (last 10)
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            email,
            subject,
            message,
            submitted_date
        FROM contact_submissions 
        WHERE status = 'new'
        ORDER BY submitted_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($messages as $message) {
        $timeAgo = getTimeAgo($message['submitted_date']);
        $notifications[] = [
            'id' => 'message_' . $message['id'],
            'type' => 'message',
            'title' => 'New Contact Message',
            'text' => $message['name'] . ': ' . substr($message['subject'], 0, 50) . (strlen($message['subject']) > 50 ? '...' : ''),
            'time' => $timeAgo,
            'timestamp' => $message['submitted_date'],
            'unread' => true,
            'icon' => 'fa-envelope',
            'color' => 'green',
            'data' => [
                'message_id' => $message['id'],
                'sender_name' => $message['name'],
                'sender_email' => $message['email']
            ]
        ];
    }
    
    // Sort all notifications by timestamp (most recent first)
    usort($notifications, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Get counts
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM admission_applications WHERE status = 'pending'");
    $stmt->execute();
    $pendingAdmissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'new'");
    $stmt->execute();
    $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'counts' => [
                'admissions' => (int)$pendingAdmissions,
                'messages' => (int)$unreadMessages,
                'total' => (int)$pendingAdmissions + (int)$unreadMessages
            ]
        ],
        // Legacy format for backward compatibility
        'admissions' => (int)$pendingAdmissions,
        'messages' => (int)$unreadMessages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

