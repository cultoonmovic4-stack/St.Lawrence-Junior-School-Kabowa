<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';

// Check authentication and permission
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

requirePermission('admission.view');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        SELECT 
            id,
            application_reference,
            academic_year,
            term,
            class_to_join,
            admission_type,
            student_surname,
            student_other_names,
            date_of_birth,
            gender,
            religion,
            tribe,
            position_in_family,
            has_attended_school,
            previous_school_name,
            previous_school_location,
            language_1,
            language_2,
            responsible_person_name,
            responsible_person_address,
            responsible_person_phone,
            responsible_person_email,
            responsible_person_postal,
            parents_live_together,
            father_name,
            father_occupation,
            father_workplace,
            father_address,
            mother_name,
            mother_occupation,
            mother_workplace,
            mother_address,
            emergency_next_of_kin,
            bed_wetting,
            has_health_handicap,
            health_handicap_details,
            doctor_name,
            doctor_location,
            is_immunized,
            other_information,
            rules_acknowledged,
            declaration_accepted,
            declaration_accepted_at,
            declaration_name,
            declaration_version,
            signature_path,
            security_token,
            submitted_date,
            status,
            reviewed_by,
            review_date,
            review_notes,
            form_fee_status,
            form_fee_receipt,
            admitted_on,
            reported_on
        FROM admission_applications
        ORDER BY submitted_date DESC
    ");
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $applications
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
