<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

requirePermission('admission.edit');

try {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $data = $_POST;
    }
    
    if (empty($data['id'])) {
        throw new Exception('Application ID is required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if application exists
    $checkStmt = $db->prepare("SELECT id FROM admission_applications WHERE id = :id");
    $checkStmt->bindParam(':id', $data['id']);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        throw new Exception('Application not found');
    }
    
    $allowedFields = [
        'academic_year', 'term', 'class_to_join', 'admission_type',
        'student_surname', 'student_other_names', 'date_of_birth', 'gender',
        'religion', 'tribe', 'position_in_family',
        'has_attended_school', 'previous_school_name', 'previous_school_location',
        'language_1', 'language_2',
        'responsible_person_name', 'responsible_person_address', 'responsible_person_phone', 'responsible_person_email', 'responsible_person_postal',
        'parents_live_together',
        'father_name', 'father_occupation', 'father_workplace', 'father_address',
        'mother_name', 'mother_occupation', 'mother_workplace', 'mother_address',
        'emergency_next_of_kin', 'bed_wetting',
        'has_health_handicap', 'health_handicap_details',
        'doctor_name', 'doctor_location', 'is_immunized',
        'other_information',
        'form_fee_status', 'form_fee_receipt', 'admitted_on', 'reported_on'
    ];
    
    $updateFields = [];
    $params = [':id' => $data['id']];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $val = $data[$field];
            if ($val === '' || $val === 'null' || $val === null) {
                $val = null;
            }
            $updateFields[] = "`$field` = :$field";
            $params[":$field"] = $val;
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('No fields provided to update');
    }
    
    $sql = "UPDATE admission_applications SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute($params)) {
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
