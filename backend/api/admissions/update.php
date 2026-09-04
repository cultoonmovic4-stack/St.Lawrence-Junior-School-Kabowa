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
    // Get form data (not JSON since we have files)
    $data = $_POST;
    
    if (empty($data['id'])) {
        throw new Exception('Application ID is required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle file uploads securely
    $uploadDir = __DIR__ . '/../../uploads/admissions/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileFields = [
        'birth_certificate' => ['col' => 'birth_certificate_url', 'cat' => UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT],
        'passport_photo' => ['col' => 'passport_photo_url', 'cat' => UploadSecurityHelper::CATEGORY_IMAGE],
        'previous_school_report' => ['col' => 'previous_school_report_url', 'cat' => UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT],
        'immunization_record' => ['col' => 'immunization_record_url', 'cat' => UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT],
        'parent_id' => ['col' => 'parent_id_url', 'cat' => UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT],
        'transfer_letter' => ['col' => 'transfer_letter_url', 'cat' => UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT]
    ];
    
    $fileUpdates = [];
    foreach ($fileFields as $fieldName => $config) {
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $uploadResult = UploadSecurityHelper::validateAndSave(
                $_FILES[$fieldName],
                $config['cat'],
                $uploadDir,
                $fieldName
            );
            $fileUpdates[$config['col']] = 'backend/uploads/admissions/' . $uploadResult['filename'];
        }
    }
    
    // Build UPDATE query
    $updateFields = [
        'student_first_name = :first_name',
        'student_last_name = :last_name',
        'date_of_birth = :dob',
        'gender = :gender',
        'nationality = :nationality',
        'religion = :religion',
        'class_to_join = :class_to_join',
        'admission_type = :admission_type',
        'parent_first_name = :parent_first',
        'parent_last_name = :parent_last',
        'parent_relationship = :relationship',
        'parent_phone = :parent_phone',
        'parent_email = :parent_email',
        'parent_address = :parent_address',
        'parent_occupation = :parent_occupation',
        'emergency_contact_name = :emergency_name',
        'emergency_contact_relationship = :emergency_relationship',
        'emergency_contact_phone = :emergency_phone'
    ];
    
    // Add file updates to query
    foreach ($fileUpdates as $column => $value) {
        $updateFields[] = "$column = :$column";
    }
    
    $sql = "UPDATE admission_applications SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    // Bind basic parameters
    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':first_name', $data['student_first_name']);
    $stmt->bindParam(':last_name', $data['student_last_name']);
    $stmt->bindParam(':dob', $data['date_of_birth']);
    $stmt->bindParam(':gender', $data['gender']);
    $stmt->bindParam(':nationality', $data['nationality']);
    $stmt->bindParam(':religion', $data['religion']);
    $stmt->bindParam(':class_to_join', $data['class_to_join']);
    $stmt->bindParam(':admission_type', $data['admission_type']);
    $stmt->bindParam(':parent_first', $data['parent_first_name']);
    $stmt->bindParam(':parent_last', $data['parent_last_name']);
    $stmt->bindParam(':relationship', $data['parent_relationship']);
    $stmt->bindParam(':parent_phone', $data['parent_phone']);
    $stmt->bindParam(':parent_email', $data['parent_email']);
    $stmt->bindParam(':parent_address', $data['parent_address']);
    $stmt->bindParam(':parent_occupation', $data['parent_occupation']);
    $stmt->bindParam(':emergency_name', $data['emergency_contact_name']);
    $stmt->bindParam(':emergency_relationship', $data['emergency_contact_relationship']);
    $stmt->bindParam(':emergency_phone', $data['emergency_contact_phone']);
    
    // Bind file parameters
    foreach ($fileUpdates as $column => $value) {
        $stmt->bindParam(":$column", $fileUpdates[$column]);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Application updated successfully!'
        ]);
    } else {
        throw new Exception('Failed to update application');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
