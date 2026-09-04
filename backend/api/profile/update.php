<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle file upload or form data
    if (isset($_FILES['profile_image'])) {
        // File upload with FormData
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        // Handle profile image upload securely
        $profileImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadResult = UploadSecurityHelper::validateAndSave(
                $_FILES['profile_image'],
                UploadSecurityHelper::CATEGORY_IMAGE,
                $uploadDir,
                'profile_' . $userId
            );
            
            $profileImage = 'backend/uploads/profiles/' . $uploadResult['filename'];
            
            // Delete old profile image if exists and safe
            $stmt = $db->prepare("SELECT profile_image FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $oldUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($oldUser && $oldUser['profile_image'] && file_exists(__DIR__ . '/../../' . $oldUser['profile_image']) && strpos(realpath(__DIR__ . '/../../' . $oldUser['profile_image']), realpath($uploadDir)) === 0) {
                @unlink(__DIR__ . '/../../' . $oldUser['profile_image']);
            }
        }
        
        // Update user profile
        if ($profileImage) {
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = :full_name, 
                    email = :email, 
                    phone = :phone,
                    profile_image = :profile_image
                WHERE id = :id
            ");
            $stmt->bindParam(':profile_image', $profileImage);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = :full_name, 
                    email = :email, 
                    phone = :phone
                WHERE id = :id
            ");
        }
        
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':id', $userId);
        
    } else {
        // JSON data without file
        $data = json_decode(file_get_contents('php://input'), true);
        
        $fullName = $data['full_name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        
        $stmt = $db->prepare("
            UPDATE users 
            SET full_name = :full_name, 
                email = :email, 
                phone = :phone
            WHERE id = :id
        ");
        
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':id', $userId);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully!'
        ]);
    } else {
        throw new Exception('Failed to update profile');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
