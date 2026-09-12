<?php
/**
 * St. Lawrence Junior School Kabowa
 * Official Admission Application Submission Controller
 * Strictly aligned with official physical application form requirements
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/Database.php';

try {
    // Read input (support JSON payload or POST)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $data = $_POST;
    }

    // Helper for string cleaning
    $getClean = function($key, $default = '') use ($data) {
        return trim((string)($data[$key] ?? $default));
    };

    // Stage 1: Application Details
    $academicYear  = $getClean('academic_year');
    $term          = $getClean('term');
    $classToJoin   = $getClean('class_to_join');
    $admissionType = strtolower($getClean('admission_type'));

    if (empty($academicYear) || empty($term) || empty($classToJoin) || empty($admissionType)) {
        throw new Exception('Please complete all application details (Year, Term, Class, and School Type).');
    }
    if (!in_array($admissionType, ['day', 'boarding'])) {
        throw new Exception('Invalid School Type selected. Choose Day School or Boarder.');
    }

    // Stage 2: Child Information
    $studentSurname    = $getClean('student_surname');
    $studentOtherNames  = $getClean('student_other_names');
    $dateOfBirth       = $getClean('date_of_birth');
    $gender            = strtolower($getClean('gender'));
    $religion          = $getClean('religion');
    $tribe             = $getClean('tribe');
    $positionInFamily  = $getClean('position_in_family');
    $hasAttendedSchool = (in_array(strtolower($getClean('has_attended_school')), ['yes', '1', 'true'])) ? 1 : 0;
    
    if (empty($studentSurname) || empty($studentOtherNames) || empty($dateOfBirth) || empty($gender) || empty($religion) || empty($tribe) || empty($positionInFamily)) {
        throw new Exception("Please fill in all required Child Information fields.");
    }
    if (!in_array($gender, ['male', 'female'])) {
        throw new Exception('Please select a valid sex (Male or Female).');
    }

    // Previous School conditional validation
    $previousSchoolName = null;
    $previousSchoolLoc  = null;
    if ($hasAttendedSchool === 1) {
        $previousSchoolName = $getClean('previous_school_name');
        $previousSchoolLoc  = $getClean('previous_school_location');
        if (empty($previousSchoolName) || empty($previousSchoolLoc)) {
            throw new Exception("Please specify the Present/Previous School Name and Location.");
        }
    }

    // Languages
    $language1 = $getClean('language_1');
    $language2 = $getClean('language_2');
    if (empty($language1)) {
        throw new Exception("Please provide at least one clear language to the child (Language 1).");
    }
    $language2 = !empty($language2) ? $language2 : null;

    // Stage 3: Person Responsible for the Child
    $responsibleName    = $getClean('responsible_person_name');
    $responsibleAddress = $getClean('responsible_person_address');
    $responsiblePhone   = $getClean('responsible_person_phone');
    $responsibleEmail   = strtolower($getClean('responsible_person_email'));
    $responsiblePostal  = $getClean('responsible_person_postal');

    if (empty($responsibleName) || empty($responsibleAddress) || empty($responsiblePhone)) {
        throw new Exception("Please provide the full details for the Person Responsible for the child (Name, Address Home/Work, Telephone).");
    }
    if (empty($responsibleEmail) || !filter_var($responsibleEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please provide a valid Email Address for official admission communication.");
    }
    $responsiblePostal = !empty($responsiblePostal) ? $responsiblePostal : null;

    // Parents Information
    $parentsLiveTogether = strtolower($getClean('parents_live_together')) === 'no' ? 'no' : 'yes';
    $fatherName          = $getClean('father_name');
    $fatherOccupation    = $getClean('father_occupation') ?: null;
    $fatherWorkplace     = $getClean('father_workplace') ?: null;
    $fatherAddress       = $getClean('father_address') ?: null;

    $motherName          = $getClean('mother_name');
    $motherOccupation    = $getClean('mother_occupation') ?: null;
    $motherWorkplace     = $getClean('mother_workplace') ?: null;
    $motherAddress       = $getClean('mother_address') ?: null;

    if (empty($fatherName) || empty($motherName)) {
        throw new Exception("Both Father's Name and Mother's Name are required.");
    }

    // Stage 4: Emergency & Health Information
    $emergencyNextOfKin = $getClean('emergency_next_of_kin');
    if (empty($emergencyNextOfKin)) {
        throw new Exception("Please provide Next of Kin in Case of Emergency.");
    }

    // Boarder bed-wetting rule
    $bedWetting = null;
    if ($admissionType === 'boarding') {
        $rawBed = strtolower($getClean('bed_wetting'));
        if (!in_array($rawBed, ['yes', 'no'])) {
            throw new Exception("For Boarders, please specify whether the child wets the bed (Yes or No).");
        }
        $bedWetting = $rawBed;
    }

    // Health Handicap rule
    $hasHealthHandicap = strtolower($getClean('has_health_handicap')) === 'yes' ? 'yes' : 'no';
    $healthHandicapDetails = null;
    if ($hasHealthHandicap === 'yes') {
        $healthHandicapDetails = $getClean('health_handicap_details');
        if (empty($healthHandicapDetails)) {
            throw new Exception("Please provide details for the child's health handicap.");
        }
    }

    $doctorName     = $getClean('doctor_name') ?: null;
    $doctorLocation = $getClean('doctor_location') ?: null;

    $isImmunized = strtolower($getClean('is_immunized'));
    if (!in_array($isImmunized, ['yes', 'no'])) {
        throw new Exception("Please indicate whether the child was immunized (Yes or No).");
    }

    // Stage 5: Other Information
    $otherInformation = $getClean('other_information') ?: null;

    // Stage 6: Rules & Regulations
    $rulesAcknowledged = !empty($data['rules_acknowledged']) ? 1 : 0;
    if (!$rulesAcknowledged) {
        throw new Exception("You must read and acknowledge the school Rules and Regulations to proceed.");
    }

    // Stage 7: Declaration & Signature
    $declarationAccepted = !empty($data['declaration_accepted']) ? 1 : 0;
    $declarationName     = $getClean('declaration_name');
    $signatureData       = $data['signature'] ?? $data['signature_data'] ?? '';

    if (!$declarationAccepted || empty($declarationName)) {
        throw new Exception("Please agree to the official declaration and provide the Parent/Guardian full name.");
    }
    if (empty($signatureData)) {
        throw new Exception("Please provide a valid parent/guardian digital signature.");
    }

    // Validate and write signature PNG image securely
    $signatureDir = __DIR__ . '/../../uploads/admissions/signatures/';
    if (!file_exists($signatureDir)) {
        mkdir($signatureDir, 0755, true);
    }

    // Extract base64 image data
    if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/i', $signatureData, $matches)) {
        $decodedSig = base64_decode($matches[2]);
    } else {
        $decodedSig = base64_decode($signatureData);
    }

    if (!$decodedSig || strlen($decodedSig) < 64) {
        throw new Exception("Invalid signature payload. Please redraw your signature.");
    }

    // Ensure directory protection .htaccess exists
    if (!file_exists($signatureDir . '.htaccess')) {
        file_put_contents($signatureDir . '.htaccess', "Options -Indexes -ExecCGI\nDeny from all\n");
    }

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    // Duplicate submission protection (within 15 minutes)
    $dupCheck = $db->prepare("
        SELECT application_reference, security_token 
        FROM admission_applications 
        WHERE student_surname = :s_sur 
          AND student_other_names = :s_oth 
          AND date_of_birth = :dob 
          AND academic_year = :yr 
          AND term = :trm 
          AND responsible_person_phone = :r_phone
          AND submitted_date >= (NOW() - INTERVAL 15 MINUTE)
        LIMIT 1
    ");
    $dupCheck->execute([
        ':s_sur' => $studentSurname,
        ':s_oth' => $studentOtherNames,
        ':dob' => $dateOfBirth,
        ':yr' => $academicYear,
        ':trm' => $term,
        ':r_phone' => $responsiblePhone
    ]);
    $existingApp = $dupCheck->fetch(PDO::FETCH_ASSOC);
    if ($existingApp) {
        echo json_encode([
            'success' => true,
            'message' => 'Your application has already been submitted successfully.',
            'application_reference' => $existingApp['application_reference'],
            'student_name' => $studentSurname . ' ' . $studentOtherNames,
            'class' => $classToJoin,
            'security_token' => $existingApp['security_token'],
            'download_url' => "../backend/api/admissions/download-pdf.php?ref=" . urlencode($existingApp['application_reference']) . "&token=" . urlencode($existingApp['security_token']),
            'blank_form_url' => "../backend/api/admissions/download-blank-pdf.php"
        ]);
        exit;
    }

    // Generate collision-resistant unique Application Reference
    $appRef = '';
    $yearPrefix = date('Y');
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidateRef = 'SLJK-' . $yearPrefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM admission_applications WHERE application_reference = :ref");
        $checkStmt->bindParam(':ref', $candidateRef);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() == 0) {
            $appRef = $candidateRef;
            break;
        }
    }

    if (empty($appRef)) {
        throw new Exception("System busy. Could not generate a unique application reference. Please try again.");
    }

    // Save signature file
    $sigFilename = 'sig_' . strtolower(str_replace('-', '_', $appRef)) . '_' . time() . '.png';
    $sigFullPath = $signatureDir . $sigFilename;
    if (file_put_contents($sigFullPath, $decodedSig) === false) {
        throw new Exception("Failed to safely store the digital signature. Please try again.");
    }
    @chmod($sigFullPath, 0644);

    $relativeSigPath = 'backend/uploads/admissions/signatures/' . $sigFilename;
    $securityToken   = bin2hex(random_bytes(32));
    $declarationDate = date('Y-m-d H:i:s');

    // Begin database transaction
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO admission_applications (
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
            status,
            form_fee_status,
            submitted_date
        ) VALUES (
            :application_reference,
            :academic_year,
            :term,
            :class_to_join,
            :admission_type,
            :student_surname,
            :student_other_names,
            :date_of_birth,
            :gender,
            :religion,
            :tribe,
            :position_in_family,
            :has_attended_school,
            :previous_school_name,
            :previous_school_location,
            :language_1,
            :language_2,
            :responsible_person_name,
            :responsible_person_address,
            :responsible_person_phone,
            :responsible_person_email,
            :responsible_person_postal,
            :parents_live_together,
            :father_name,
            :father_occupation,
            :father_workplace,
            :father_address,
            :mother_name,
            :mother_occupation,
            :mother_workplace,
            :mother_address,
            :emergency_next_of_kin,
            :bed_wetting,
            :has_health_handicap,
            :health_handicap_details,
            :doctor_name,
            :doctor_location,
            :is_immunized,
            :other_information,
            :rules_acknowledged,
            :declaration_accepted,
            :declaration_accepted_at,
            :declaration_name,
            'v1.0',
            :signature_path,
            :security_token,
            'pending',
            'unpaid',
            NOW()
        )
    ");

    $stmt->bindValue(':application_reference', $appRef);
    $stmt->bindValue(':academic_year', $academicYear);
    $stmt->bindValue(':term', $term);
    $stmt->bindValue(':class_to_join', $classToJoin);
    $stmt->bindValue(':admission_type', $admissionType);
    $stmt->bindValue(':student_surname', $studentSurname);
    $stmt->bindValue(':student_other_names', $studentOtherNames);
    $stmt->bindValue(':date_of_birth', $dateOfBirth);
    $stmt->bindValue(':gender', $gender);
    $stmt->bindValue(':religion', $religion);
    $stmt->bindValue(':tribe', $tribe);
    $stmt->bindValue(':position_in_family', $positionInFamily);
    $stmt->bindValue(':has_attended_school', $hasAttendedSchool, PDO::PARAM_INT);
    $stmt->bindValue(':previous_school_name', $previousSchoolName);
    $stmt->bindValue(':previous_school_location', $previousSchoolLoc);
    $stmt->bindValue(':language_1', $language1);
    $stmt->bindValue(':language_2', $language2);
    $stmt->bindValue(':responsible_person_name', $responsibleName);
    $stmt->bindValue(':responsible_person_address', $responsibleAddress);
    $stmt->bindValue(':responsible_person_phone', $responsiblePhone);
    $stmt->bindValue(':responsible_person_email', $responsibleEmail);
    $stmt->bindValue(':responsible_person_postal', $responsiblePostal);
    $stmt->bindValue(':parents_live_together', $parentsLiveTogether);
    $stmt->bindValue(':father_name', $fatherName);
    $stmt->bindValue(':father_occupation', $fatherOccupation);
    $stmt->bindValue(':father_workplace', $fatherWorkplace);
    $stmt->bindValue(':father_address', $fatherAddress);
    $stmt->bindValue(':mother_name', $motherName);
    $stmt->bindValue(':mother_occupation', $motherOccupation);
    $stmt->bindValue(':mother_workplace', $motherWorkplace);
    $stmt->bindValue(':mother_address', $motherAddress);
    $stmt->bindValue(':emergency_next_of_kin', $emergencyNextOfKin);
    $stmt->bindValue(':bed_wetting', $bedWetting);
    $stmt->bindValue(':has_health_handicap', $hasHealthHandicap);
    $stmt->bindValue(':health_handicap_details', $healthHandicapDetails);
    $stmt->bindValue(':doctor_name', $doctorName);
    $stmt->bindValue(':doctor_location', $doctorLocation);
    $stmt->bindValue(':is_immunized', $isImmunized);
    $stmt->bindValue(':other_information', $otherInformation);
    $stmt->bindValue(':rules_acknowledged', $rulesAcknowledged, PDO::PARAM_INT);
    $stmt->bindValue(':declaration_accepted', $declarationAccepted, PDO::PARAM_INT);
    $stmt->bindValue(':declaration_accepted_at', $declarationDate);
    $stmt->bindValue(':declaration_name', $declarationName);
    $stmt->bindValue(':signature_path', $relativeSigPath);
    $stmt->bindValue(':security_token', $securityToken);

    $stmt->execute();
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!',
        'application_reference' => $appRef,
        'student_name' => $studentSurname . ' ' . $studentOtherNames,
        'class' => $classToJoin,
        'security_token' => $securityToken,
        'download_url' => "../backend/api/admissions/download-pdf.php?ref={$appRef}&token={$securityToken}",
        'blank_form_url' => "../backend/api/admissions/download-blank-pdf.php"
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
