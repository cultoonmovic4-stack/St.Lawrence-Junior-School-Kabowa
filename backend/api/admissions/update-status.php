<?php
// Disable all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any errors
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';

// Clear any output that might have been generated
if (ob_get_length()) ob_clean();

// Check authentication
if (!isAuthenticated()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

requirePermission('admission.approve');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id']) || empty($data['status'])) {
        throw new Exception('Application ID and status are required');
    }
    
    $status = strtolower(trim($data['status']));
    if ($status === 'waitlisted') {
        $status = 'waitlist';
    }
    
    $validStatuses = ['pending', 'under_review', 'accepted', 'rejected', 'waitlist'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get application details
    $stmt = $db->prepare("SELECT * FROM admission_applications WHERE id = :id");
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    // Update status
    $currentUser = getCurrentUser();
    $reviewNotes = $data['notes'] ?? '';
    $userId = $currentUser['user_id'] ?? null;
    
    $stmt = $db->prepare("
        UPDATE admission_applications 
        SET status = :status, 
            reviewed_by = :reviewed_by, 
            review_date = NOW(),
            review_notes = :review_notes
        WHERE id = :id
    ");
    
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':reviewed_by', $userId);
    $stmt->bindParam(':review_notes', $reviewNotes);
    $stmt->bindParam(':id', $data['id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update status');
    }
    
    // Send email notification via AdmissionEmailService
    require_once __DIR__ . '/../helpers/AdmissionEmailService.php';
    
    $oldStatus = $application['status'] ?? null;
    $emailResult = AdmissionEmailService::sendStatusNotification(
        $db, 
        (int)$data['id'], 
        $status, 
        $oldStatus, 
        $reviewNotes
    );
    $emailStatus = $emailResult['message'] ?? 'Notification processed';
    
    // Clear output buffer and send JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Application status updated to '{$status}'.",
        'email_status' => $emailStatus,
        'email_sent' => $emailResult['email_sent'] ?? false
    ]);
    ob_end_flush();
    
} catch (Exception $e) {
    // Clear any output buffer
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
