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
    // Get form data
    $full_name = $_POST['full_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $experience_years = $_POST['experience_years'] ?? null;
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    
    // Validate required fields
    if (empty($full_name) || empty($department)) {
        throw new Exception('Name and department are required');
    }
    
    // Validate department
    $validDepartments = ['administration', 'english', 'mathematics', 'science', 'social'];
    if (!in_array($department, $validDepartments)) {
        throw new Exception('Invalid department');
    }
    
    // Handle photo upload securely
    $photoUrl = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/teachers/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['photo'],
            UploadSecurityHelper::CATEGORY_IMAGE,
            $uploadDir,
            'teacher'
        );
        $photoUrl = 'uploads/teachers/' . $uploadResult['filename'];
    }
    
    // Save to database
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO teachers 
        (full_name, department, position, qualification, experience_years, email, phone, photo_url, bio, specialization, status) 
        VALUES 
        (:full_name, :department, :position, :qualification, :experience_years, :email, :phone, :photo_url, :bio, :specialization, 'active')
    ");
    
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':position', $position);
    $stmt->bindParam(':qualification', $qualification);
    $stmt->bindParam(':experience_years', $experience_years);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':photo_url', $photoUrl);
    $stmt->bindParam(':bio', $bio);
    $stmt->bindParam(':specialization', $specialization);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Teacher added successfully!',
            'data' => [
                'id' => $db->lastInsertId()
            ]
        ]);
    } else {
        throw new Exception('Failed to save to database');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
