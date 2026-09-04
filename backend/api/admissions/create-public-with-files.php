<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../helpers/UploadSecurityHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get form data from POST
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $dateOfBirth = $_POST['dateOfBirth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $classToJoin = $_POST['classToJoin'] ?? '';
    $medicalConditions = $_POST['medicalConditions'] ?? '';
    
    // Parent/Guardian Information
    $parentName = $_POST['parentName'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $parentEmail = $_POST['parentEmail'] ?? '';
    $parentPhone = $_POST['parentPhone'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $nin = $_POST['nin'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Emergency Contact
    $emergencyName = $_POST['emergencyName'] ?? '';
    $emergencyRelationship = $_POST['emergencyRelationship'] ?? '';
    $emergencyPhone = $_POST['emergencyPhone'] ?? '';
    $emergencyAddress = $_POST['emergencyAddress'] ?? '';
    
    // Additional Information
    $previousSchool = $_POST['previousSchool'] ?? '';
    $allergies = $_POST['allergies'] ?? '';
    $specialNeeds = $_POST['specialNeeds'] ?? '';
    $hearAboutUs = $_POST['hearAboutUs'] ?? '';
    $additionalComments = $_POST['additionalComments'] ?? '';
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($dateOfBirth) || empty($gender) || 
        empty($nationality) || empty($classToJoin) || empty($parentName) || 
        empty($parentEmail) || empty($parentPhone) || empty($occupation) || 
        empty($nin) || empty($address) || empty($emergencyName) || 
        empty($emergencyRelationship) || empty($emergencyPhone)) {
        throw new Exception('Please fill in all required fields');
    }
    
    // Validate email
    if (!filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . '/../../uploads/admissions/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Handle file uploads securely
    $birthCertificateUrl = '';
    $passportPhotoUrl = '';
    $previousReportUrl = '';
    $immunizationUrl = '';
    $parentIdUrl = '';
    $transferLetterUrl = '';
    
    // Upload Birth Certificate (Document/PDF/Image)
    if (isset($_FILES['birthCertificate']) && $_FILES['birthCertificate']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['birthCertificate'],
            UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT,
            $uploadDir,
            'birth_cert'
        );
        $birthCertificateUrl = 'uploads/admissions/' . $uploadResult['filename'];
    }
    
    // Upload Passport Photo (Image)
    if (isset($_FILES['passportPhoto']) && $_FILES['passportPhoto']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['passportPhoto'],
            UploadSecurityHelper::CATEGORY_IMAGE,
            $uploadDir,
            'passport'
        );
        $passportPhotoUrl = 'uploads/admissions/' . $uploadResult['filename'];
    }
    
    // Upload Previous Report (Document/PDF/Image)
    if (isset($_FILES['previousReport']) && $_FILES['previousReport']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['previousReport'],
            UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT,
            $uploadDir,
            'report'
        );
        $previousReportUrl = 'uploads/admissions/' . $uploadResult['filename'];
    }
    
    // Upload Immunization Records (Document/PDF/Image)
    if (isset($_FILES['immunizationRecords']) && $_FILES['immunizationRecords']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['immunizationRecords'],
            UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT,
            $uploadDir,
            'immunization'
        );
        $immunizationUrl = 'uploads/admissions/' . $uploadResult['filename'];
    }
    
    // Upload Parent ID (Document/PDF/Image)
    if (isset($_FILES['parentId']) && $_FILES['parentId']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['parentId'],
            UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT,
            $uploadDir,
            'parent_id'
        );
        $parentIdUrl = 'uploads/admissions/' . $uploadResult['filename'];
    }
    
    // Upload Transfer Letter (Document/PDF/Image)
    if (isset($_FILES['transferLetter']) && $_FILES['transferLetter']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['transferLetter'],
            UploadSecurityHelper::CATEGORY_ADMISSION_DOCUMENT,
            $uploadDir,
            'transfer'
        );
        $transferLetterUrl = 'uploads/admissions/' . $uploadResult['filename'];
    }
    
    // Save to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate unique application ID
    $applicationId = 'APP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $stmt = $db->prepare("
        INSERT INTO admission_applications 
        (application_id, student_first_name, student_last_name, date_of_birth, gender, nationality, religion, class_to_join, admission_type,
         parent_first_name, parent_last_name, parent_relationship, parent_phone, parent_email, parent_address, parent_occupation,
         emergency_contact_name, emergency_contact_relationship, emergency_contact_phone,
         birth_certificate_url, previous_school_report_url, passport_photo_url, immunization_record_url, parent_id_url, transfer_letter_url,
         status, submitted_date) 
        VALUES 
        (:application_id, :first_name, :last_name, :dob, :gender, :nationality, :religion, :class_to_join, 'day',
         :parent_first, :parent_last, :relationship, :parent_phone, :parent_email, :parent_address, :parent_occupation,
         :emergency_name, :emergency_relationship, :emergency_phone,
         :birth_cert, :previous_report, :passport_photo, :immunization, :parent_id, :transfer_letter,
         'pending', NOW())
    ");
    
    // Split parent name if it's a single field
    $parentNameParts = explode(' ', $parentName, 2);
    $parentFirstName = $parentNameParts[0];
    $parentLastName = isset($parentNameParts[1]) ? $parentNameParts[1] : '';
    
    $stmt->bindParam(':application_id', $applicationId);
    $stmt->bindParam(':first_name', $firstName);
    $stmt->bindParam(':last_name', $lastName);
    $stmt->bindParam(':dob', $dateOfBirth);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':nationality', $nationality);
    $stmt->bindParam(':religion', $religion);
    $stmt->bindParam(':class_to_join', $classToJoin);
    $stmt->bindParam(':parent_first', $parentFirstName);
    $stmt->bindParam(':parent_last', $parentLastName);
    $stmt->bindParam(':relationship', $relationship);
    $stmt->bindParam(':parent_phone', $parentPhone);
    $stmt->bindParam(':parent_email', $parentEmail);
    $stmt->bindParam(':parent_address', $address);
    $stmt->bindParam(':parent_occupation', $occupation);
    $stmt->bindParam(':emergency_name', $emergencyName);
    $stmt->bindParam(':emergency_relationship', $emergencyRelationship);
    $stmt->bindParam(':emergency_phone', $emergencyPhone);
    $stmt->bindParam(':birth_cert', $birthCertificateUrl);
    $stmt->bindParam(':previous_report', $previousReportUrl);
    $stmt->bindParam(':passport_photo', $passportPhotoUrl);
    $stmt->bindParam(':immunization', $immunizationUrl);
    $stmt->bindParam(':parent_id', $parentIdUrl);
    $stmt->bindParam(':transfer_letter', $transferLetterUrl);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully!',
            'application_id' => $applicationId
        ]);
    } else {
        throw new Exception('Failed to save application');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
