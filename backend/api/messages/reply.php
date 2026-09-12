<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/ContactReplyEmailService.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['submission_id']) || empty($data['reply_message'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields (submission_id or reply_message)'
        ]);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? 1;
    $adminName = $currentUser['full_name'] ?? $currentUser['username'] ?? 'School Administration';
    
    // Get full original submission details
    $getStmt = $db->prepare("SELECT id, name, email, phone, subject, message, submitted_date FROM contact_submissions WHERE id = :id LIMIT 1");
    $getStmt->bindParam(':id', $data['submission_id'], PDO::PARAM_INT);
    $getStmt->execute();
    $submission = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        throw new Exception('Contact submission not found');
    }
    
    $replyMessage = trim($data['reply_message']);
    
    // Insert reply into contact_replies
    $stmt = $db->prepare("
        INSERT INTO contact_replies 
        (contact_submission_id, replied_by, reply_message, reply_date) 
        VALUES 
        (:submission_id, :replied_by, :reply_message, NOW())
    ");
    
    $stmt->bindParam(':submission_id', $data['submission_id'], PDO::PARAM_INT);
    $stmt->bindParam(':replied_by', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':reply_message', $replyMessage);
    
    if ($stmt->execute()) {
        // Update contact_submissions status to 'replied'
        $updateStmt = $db->prepare("
            UPDATE contact_submissions 
            SET status = 'replied', 
                replied_by = :replied_by, 
                reply_date = NOW(),
                reply_message = :reply_message
            WHERE id = :submission_id
        ");
        
        $updateStmt->bindParam(':replied_by', $userId, PDO::PARAM_INT);
        $updateStmt->bindParam(':reply_message', $replyMessage);
        $updateStmt->bindParam(':submission_id', $data['submission_id'], PDO::PARAM_INT);
        $updateStmt->execute();
        
        // Dispatch styled institutional email using ContactReplyEmailService
        $emailResult = ContactReplyEmailService::sendReplyEmail(
            $db,
            $submission,
            $replyMessage,
            $adminName
        );
        
        echo json_encode([
            'success'     => true,
            'message'     => 'Institutional reply successfully recorded' . ($emailResult['success'] ? ' and email dispatched to ' . htmlspecialchars($submission['email']) : ' (email dispatch status: ' . ($emailResult['error_message'] ?? 'could not send') . ')'),
            'email_sent'  => $emailResult['success'],
            'email_error' => $emailResult['error_message'] ?? null
        ]);
    } else {
        throw new Exception('Failed to save message reply');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
