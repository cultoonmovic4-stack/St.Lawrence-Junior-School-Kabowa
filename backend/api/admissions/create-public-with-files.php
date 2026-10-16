<?php
/**
 * St. Lawrence Junior School Kabowa
 * Official Admission Application Submission Controller
 * Strictly aligned with official physical application form requirements
 */

require_once __DIR__ . '/../config/cors.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

// Diagnostic & self-healing trigger
if (isset($_GET['diag_schema'])) {
    try {
        $db = (new Database())->getConnection();
        $cols = $db->query("SHOW COLUMNS FROM admission_applications")->fetchAll(PDO::FETCH_COLUMN);

        $neededCols = [
            'application_reference' => "VARCHAR(50) DEFAULT NULL",
            'academic_year' => "VARCHAR(20) DEFAULT '2026'",
            'term' => "VARCHAR(30) DEFAULT 'Term 1'",
            'student_surname' => "VARCHAR(100) DEFAULT ''",
            'student_other_names' => "VARCHAR(150) DEFAULT ''",
            'tribe' => "VARCHAR(100) DEFAULT NULL",
            'position_in_family' => "VARCHAR(100) DEFAULT NULL",
            'has_attended_school' => "TINYINT(1) DEFAULT 0",
            'previous_school_name' => "VARCHAR(200) DEFAULT NULL",
            'previous_school_location' => "VARCHAR(200) DEFAULT NULL",
            'language_1' => "VARCHAR(100) DEFAULT 'English'",
            'language_2' => "VARCHAR(100) DEFAULT NULL",
            'responsible_person_name' => "VARCHAR(200) DEFAULT NULL",
            'responsible_person_address' => "TEXT DEFAULT NULL",
            'responsible_person_phone' => "VARCHAR(30) DEFAULT NULL",
            'responsible_person_email' => "VARCHAR(255) DEFAULT NULL",
            'responsible_person_postal' => "VARCHAR(100) DEFAULT NULL",
            'parents_live_together' => "VARCHAR(10) DEFAULT 'yes'",
            'father_name' => "VARCHAR(200) DEFAULT NULL",
            'father_occupation' => "VARCHAR(150) DEFAULT NULL",
            'father_workplace' => "VARCHAR(200) DEFAULT NULL",
            'father_address' => "TEXT DEFAULT NULL",
            'mother_name' => "VARCHAR(200) DEFAULT NULL",
            'mother_occupation' => "VARCHAR(150) DEFAULT NULL",
            'mother_workplace' => "VARCHAR(200) DEFAULT NULL",
            'mother_address' => "TEXT DEFAULT NULL",
            'emergency_next_of_kin' => "VARCHAR(200) DEFAULT NULL",
            'bed_wetting' => "VARCHAR(10) DEFAULT NULL",
            'has_health_handicap' => "VARCHAR(10) DEFAULT 'no'",
            'health_handicap_details' => "TEXT DEFAULT NULL",
            'doctor_name' => "VARCHAR(150) DEFAULT NULL",
            'doctor_location' => "VARCHAR(200) DEFAULT NULL",
            'is_immunized' => "VARCHAR(10) DEFAULT 'yes'",
            'other_information' => "TEXT DEFAULT NULL",
            'rules_acknowledged' => "TINYINT(1) DEFAULT 0",
            'declaration_accepted' => "TINYINT(1) DEFAULT 0",
            'declaration_accepted_at' => "DATETIME DEFAULT NULL",
            'declaration_name' => "VARCHAR(200) DEFAULT NULL",
            'declaration_version' => "VARCHAR(20) DEFAULT 'v1.0'",
            'signature_path' => "VARCHAR(255) DEFAULT NULL",
            'security_token' => "VARCHAR(64) DEFAULT NULL",
            'form_fee_status' => "VARCHAR(50) DEFAULT 'unpaid'",
            'submitted_date' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        $migResults = [];
        foreach ($neededCols as $c => $def) {
            if (!in_array($c, $cols)) {
                try {
                    $db->exec("ALTER TABLE admission_applications ADD COLUMN `{$c}` {$def}");
                    $migResults[$c] = 'ADDED';
                } catch (\Throwable $ex) {
                    $migResults[$c] = 'ERROR: ' . $ex->getMessage();
                }
            } else {
                $migResults[$c] = 'EXISTS';
            }
        }
        $afterCols = $db->query("SHOW COLUMNS FROM admission_applications")->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode([
            'commit' => 'pos_params_v5',
            'initial_cols' => $cols,
            'migration' => $migResults,
            'after_cols' => $afterCols
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['commit' => 'pos_params_v5', 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    // Read input (support JSON payload or POST)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $data = $_POST;
    }

    // Helper for string cleaning
    $getClean = function ($key, $default = '') use ($data) {
        return trim((string) ($data[$key] ?? $default));
    };

    // ─── Layer 0: Anti-Bot Honeypot ──────────────────────────────────────────
    $honeypot = $getClean('admission_website_hp');
    if (!empty($honeypot)) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'Spam detected. Application could not be submitted.'
        ]);
        exit;
    }

    // ─── Layer 0B: Time-gate Check (prevent instant automated scripts) ───────
    $loadTime = (int) $getClean('admission_load_time', 0);
    if ($loadTime > 0) {
        $elapsed = time() - $loadTime;
        if ($elapsed < 5) {
            throw new Exception("Application submitted too quickly ($elapsed seconds). Please take a moment to carefully review your form.");
        }
    }

    // ─── Layer 0C: Mathematical Security Challenge Check ────────────────────
    $secA = (int) $getClean('admission_security_a', 0);
    $secB = (int) $getClean('admission_security_b', 0);
    $secAns = (int) $getClean('admission_security_answer', 0);
    if ($secA > 0 && $secB > 0) {
        if (($secA + $secB) !== $secAns) {
            throw new Exception("The security math verification answer was incorrect. Please solve the calculation to submit.");
        }
    }

    // ─── Helper: Validate Authentic Personal Name ────────────────────────────
    $validateName = function ($val, $fieldLabel) {
        if (mb_strlen($val) < 2) {
            throw new Exception("{$fieldLabel} must be at least 2 characters long.");
        }
        if (!preg_match('/[a-zA-Z]/', $val)) {
            throw new Exception("{$fieldLabel} must contain valid letters.");
        }
        $lower = strtolower($val);
        $dummyNames = ['test', 'asdf', 'qwer', 'xyz', 'none', 'n/a', 'na', 'unknown', 'fake', 'abc', 'random', 'testing', 'sample', 'demo', 'null'];
        if (in_array($lower, $dummyNames, true)) {
            throw new Exception("Please provide a genuine {$fieldLabel}, not a placeholder.");
        }
    };

    // Stage 1: Application Details
    $academicYear = $getClean('academic_year');
    $term = $getClean('term');
    $classToJoin = $getClean('class_to_join');
    $admissionType = strtolower($getClean('admission_type'));

    if (empty($academicYear) || empty($term) || empty($classToJoin) || empty($admissionType)) {
        throw new Exception('Please complete all application details (Year, Term, Class, and School Type).');
    }
    if (!in_array($admissionType, ['day', 'boarding'])) {
        throw new Exception('Invalid School Type selected. Choose Day School or Boarder.');
    }

    // Stage 2: Child Information
    $studentSurname = $getClean('student_surname');
    $studentOtherNames = $getClean('student_other_names');
    $dateOfBirth = $getClean('date_of_birth');
    $gender = strtolower($getClean('gender'));
    $religion = $getClean('religion');
    $tribe = $getClean('tribe');
    $positionInFamily = $getClean('position_in_family');
    $hasAttendedSchool = (in_array(strtolower($getClean('has_attended_school')), ['yes', '1', 'true'])) ? 1 : 0;

    if (empty($studentSurname) || empty($studentOtherNames) || empty($dateOfBirth) || empty($gender) || empty($religion) || empty($tribe) || empty($positionInFamily)) {
        throw new Exception("Please fill in all required Child Information fields.");
    }

    $validateName($studentSurname, "Child's Surname");
    $validateName($studentOtherNames, "Child's Other Names");
    $validateName($religion, "Religion");
    $validateName($tribe, "Tribe");

    // Realistic Date of Birth verification
    $dobTime = strtotime($dateOfBirth);
    if (!$dobTime || $dobTime > time()) {
        throw new Exception("Child's Date of Birth cannot be in the future.");
    }
    $ageYears = (time() - $dobTime) / (365.25 * 86400);
    if ($ageYears < 1.5 || $ageYears > 18) {
        throw new Exception("Please check the Child's Date of Birth. Age must be between 2 and 18 years for nursery/primary admission.");
    }

    if (!in_array($gender, ['male', 'female'])) {
        throw new Exception('Please select a valid sex (Male or Female).');
    }

    // Previous School conditional validation
    $previousSchoolName = null;
    $previousSchoolLoc = null;
    if ($hasAttendedSchool === 1) {
        $previousSchoolName = $getClean('previous_school_name');
        $previousSchoolLoc = $getClean('previous_school_location');
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
    $responsibleName = $getClean('responsible_person_name');
    $responsibleAddress = $getClean('responsible_person_address');
    $responsiblePhone = $getClean('responsible_person_phone');
    $responsibleEmail = strtolower($getClean('responsible_person_email'));
    $responsiblePostal = $getClean('responsible_person_postal');

    if (empty($responsibleName) || empty($responsibleAddress) || empty($responsiblePhone)) {
        throw new Exception("Please provide the full details for the Person Responsible for the child (Name, Address Home/Work, Telephone).");
    }

    $validateName($responsibleName, "Responsible Person's Name");

    // Strict Telephone Validation (Ugandan & international)
    $cleanPhone = preg_replace('/[\s\-\(\)\.]/', '', $responsiblePhone);
    if (!preg_match('/^(\+?[0-9]{9,15})$/', $cleanPhone)) {
        throw new Exception("Please provide a valid active Telephone Number (e.g. 0772420506 or +256772420506).");
    }
    $digitsOnly = preg_replace('/^\+?[0-9]{1,3}/', '', $cleanPhone);
    if (preg_match('/^(\d)\1{5,}$/', $digitsOnly) || in_array($cleanPhone, ['123456789', '1234567890', '0123456789', '0987654321', '0700000000', '0770000000'])) {
        throw new Exception("Please provide an active, genuine telephone number for official communication.");
    }

    // Strict Email Validation & Anti-Fake Domain Screening
    if (empty($responsibleEmail) || !filter_var($responsibleEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please provide a valid, active Email Address for official admission communication.");
    }

    $emailParts = explode('@', $responsibleEmail);
    $emailUser = strtolower(trim($emailParts[0] ?? ''));
    $emailDomain = strtolower(trim(end($emailParts)));

    // 1. Reject dummy usernames
    $dummyUsers = ['test', 'fake', 'asdf', 'admin', 'none', 'noemail', 'na', 'n/a', 'abc', 'xyz', '123', 'qwerty', 'temp', 'null', 'user', 'email'];
    if (in_array($emailUser, $dummyUsers, true) || strlen($emailUser) < 2) {
        throw new Exception("Please provide a genuine parent/guardian email address, not a placeholder account.");
    }

    // 2. Reject fake / placeholder domains
    $dummyDomains = [
        'example.com', 'example.org', 'example.net', 'test.com', 'test.org', 'fake.com',
        'none.com', 'noemail.com', 'sample.com', 'domain.com', 'invalid.com', 'email.com',
        'asdf.com', 'demo.com', 'testing.com', 'localhost', 'mysite.com', 'placeholder.com'
    ];
    if (in_array($emailDomain, $dummyDomains, true)) {
        throw new Exception("The email domain '@{$emailDomain}' is not a valid provider. Please provide a genuine email address.");
    }

    // 3. Reject disposable, temporary, and burner domains
    $disposableDomains = [
        'mailinator.com', 'guerrillamail.com', 'tempmail.com', '10minutemail.com',
        'yopmail.com', 'trashmail.com', 'sharklasers.com', 'dispostable.com',
        'getairmail.com', 'throwawaymail.com', 'maildrop.cc', 'inboxkitten.com',
        'temp-mail.org', 'fakeinbox.com', 'mytemp.email', 'emailondeck.com',
        'burnermail.io', 'generator.email', 'mytempemail.com', 'trashmail.net',
        'tempmailo.com', 'mohmal.com', 'crazymailing.com', 'fake-box.com',
        'guerrillamailblock.com', 'dropmail.me', '10mail.org', 'trash-mail.com',
        'guerrillamail.info', 'guerrillamail.biz', 'guerrillamail.net', 'guerrillamail.org',
        'fakemailgenerator.com', 'tempinbox.com', 'zillamail.com'
    ];
    if (in_array($emailDomain, $disposableDomains, true)) {
        throw new Exception("Temporary or disposable email addresses are not accepted for official admissions. Please provide your permanent email address.");
    }

    // 4. Live DNS MX / A record verification (ensure domain actually exists and accepts mail)
    if (function_exists('checkdnsrr')) {
        $hasMx = checkdnsrr($emailDomain, 'MX');
        $hasA = checkdnsrr($emailDomain, 'A');
        if (!$hasMx && !$hasA) {
            throw new Exception("The email domain '@{$emailDomain}' does not exist or has no active mail server. Please provide a valid email address.");
        }
    }

    $responsiblePostal = !empty($responsiblePostal) ? $responsiblePostal : null;

    // Parents Information
    $parentsLiveTogether = strtolower($getClean('parents_live_together')) === 'no' ? 'no' : 'yes';
    $fatherName = $getClean('father_name');
    $fatherOccupation = $getClean('father_occupation') ?: null;
    $fatherWorkplace = $getClean('father_workplace') ?: null;
    $fatherAddress = $getClean('father_address') ?: null;

    $motherName = $getClean('mother_name');
    $motherOccupation = $getClean('mother_occupation') ?: null;
    $motherWorkplace = $getClean('mother_workplace') ?: null;
    $motherAddress = $getClean('mother_address') ?: null;

    if (empty($fatherName) || empty($motherName)) {
        throw new Exception("Both Father's Name and Mother's Name are required.");
    }
    $validateName($fatherName, "Father's Name");
    $validateName($motherName, "Mother's Name");

    // Stage 4: Emergency & Health Information
    $emergencyNextOfKin = $getClean('emergency_next_of_kin');
    if (empty($emergencyNextOfKin)) {
        throw new Exception("Please provide Next of Kin in Case of Emergency.");
    }
    $validateName($emergencyNextOfKin, "Next of Kin Name");

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

    $doctorName = $getClean('doctor_name') ?: null;
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
    $declarationName = $getClean('declaration_name');
    $signatureData = $data['signature'] ?? $data['signature_data'] ?? '';

    if (!$declarationAccepted || empty($declarationName)) {
        throw new Exception("Please agree to the official declaration and provide the Parent/Guardian full name.");
    }
    $validateName($declarationName, "Declaration Signer's Name");
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

    // Auto-migrate schema: ensure all required columns exist in admission_applications BEFORE any queries
    $existingCols = [];
    try {
        $colStmt = $db->query("SHOW COLUMNS FROM admission_applications");
        while ($r = $colStmt->fetch(PDO::FETCH_ASSOC)) {
            $existingCols[$r['Field']] = true;
        }

        // 1. Relax legacy columns that were NOT NULL in earlier schema dumps so they don't block inserts
        $oldColsToRelax = [
            'application_id', 'student_first_name', 'student_last_name',
            'parent_first_name', 'parent_last_name', 'parent_relationship',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
            'nationality'
        ];
        foreach ($oldColsToRelax as $oldCol) {
            if (isset($existingCols[$oldCol])) {
                try {
                    $db->exec("ALTER TABLE admission_applications MODIFY COLUMN `{$oldCol}` VARCHAR(255) NULL DEFAULT NULL");
                } catch (\Throwable $e) {}
            }
        }

        // 2. Add missing columns using robust standard SQL (no syntax errors, no index conflicts)
        $neededCols = [
            'application_reference' => "VARCHAR(50) DEFAULT NULL",
            'academic_year' => "VARCHAR(20) DEFAULT '2026'",
            'term' => "VARCHAR(30) DEFAULT 'Term 1'",
            'student_surname' => "VARCHAR(100) DEFAULT ''",
            'student_other_names' => "VARCHAR(150) DEFAULT ''",
            'tribe' => "VARCHAR(100) DEFAULT NULL",
            'position_in_family' => "VARCHAR(100) DEFAULT NULL",
            'has_attended_school' => "TINYINT(1) DEFAULT 0",
            'previous_school_name' => "VARCHAR(200) DEFAULT NULL",
            'previous_school_location' => "VARCHAR(200) DEFAULT NULL",
            'language_1' => "VARCHAR(100) DEFAULT 'English'",
            'language_2' => "VARCHAR(100) DEFAULT NULL",
            'responsible_person_name' => "VARCHAR(200) DEFAULT NULL",
            'responsible_person_address' => "TEXT DEFAULT NULL",
            'responsible_person_phone' => "VARCHAR(30) DEFAULT NULL",
            'responsible_person_email' => "VARCHAR(255) DEFAULT NULL",
            'responsible_person_postal' => "VARCHAR(100) DEFAULT NULL",
            'parents_live_together' => "VARCHAR(10) DEFAULT 'yes'",
            'father_name' => "VARCHAR(200) DEFAULT NULL",
            'father_occupation' => "VARCHAR(150) DEFAULT NULL",
            'father_workplace' => "VARCHAR(200) DEFAULT NULL",
            'father_address' => "TEXT DEFAULT NULL",
            'mother_name' => "VARCHAR(200) DEFAULT NULL",
            'mother_occupation' => "VARCHAR(150) DEFAULT NULL",
            'mother_workplace' => "VARCHAR(200) DEFAULT NULL",
            'mother_address' => "TEXT DEFAULT NULL",
            'emergency_next_of_kin' => "VARCHAR(200) DEFAULT NULL",
            'bed_wetting' => "VARCHAR(10) DEFAULT NULL",
            'has_health_handicap' => "VARCHAR(10) DEFAULT 'no'",
            'health_handicap_details' => "TEXT DEFAULT NULL",
            'doctor_name' => "VARCHAR(150) DEFAULT NULL",
            'doctor_location' => "VARCHAR(200) DEFAULT NULL",
            'is_immunized' => "VARCHAR(10) DEFAULT 'yes'",
            'other_information' => "TEXT DEFAULT NULL",
            'rules_acknowledged' => "TINYINT(1) DEFAULT 0",
            'declaration_accepted' => "TINYINT(1) DEFAULT 0",
            'declaration_accepted_at' => "DATETIME DEFAULT NULL",
            'declaration_name' => "VARCHAR(200) DEFAULT NULL",
            'declaration_version' => "VARCHAR(20) DEFAULT 'v1.0'",
            'signature_path' => "VARCHAR(255) DEFAULT NULL",
            'security_token' => "VARCHAR(64) DEFAULT NULL",
            'form_fee_status' => "VARCHAR(50) DEFAULT 'unpaid'",
            'submitted_date' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];

        foreach ($neededCols as $colName => $colDef) {
            if (!isset($existingCols[$colName])) {
                try {
                    $db->exec("ALTER TABLE admission_applications ADD COLUMN `{$colName}` {$colDef}");
                    $existingCols[$colName] = true;
                } catch (\Throwable $colErr) {
                    error_log("Failed adding column {$colName}: " . $colErr->getMessage());
                }
            }
        }
    } catch (\Throwable $migEx) {
        error_log('[Admission Schema Check Error] ' . $migEx->getMessage());
    }

    // Duplicate submission protection (within 15 minutes) - only run if relevant columns exist
    if (isset($existingCols['application_reference']) && isset($existingCols['student_surname'])) {
        try {
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
        } catch (\Throwable $dupEx) {
            error_log('[Admission Dup Check Error] ' . $dupEx->getMessage());
        }
    }

    // Generate collision-resistant unique Application Reference
    $appRef = '';
    $yearPrefix = date('Y');
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidateRef = 'SLJK-' . $yearPrefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        $refCol = isset($existingCols['application_reference']) ? 'application_reference' : (isset($existingCols['application_id']) ? 'application_id' : null);
        if ($refCol) {
            try {
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM admission_applications WHERE `{$refCol}` = :ref");
                $checkStmt->bindParam(':ref', $candidateRef);
                $checkStmt->execute();
                if ($checkStmt->fetchColumn() == 0) {
                    $appRef = $candidateRef;
                    break;
                }
            } catch (\Throwable $chkEx) {
                $appRef = $candidateRef;
                break;
            }
        } else {
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
    $securityToken = bin2hex(random_bytes(32));
    $declarationDate = date('Y-m-d H:i:s');

    // Begin database transaction
    $db->beginTransaction();

    // Build robust 1-to-1 column and placeholder map (prevents SQLSTATE[HY093] duplicate or missing parameter errors)
    $rowValues = [
        'application_reference' => $appRef,
        'application_id' => $appRef,
        'academic_year' => $academicYear,
        'term' => $term,
        'class_to_join' => $classToJoin,
        'admission_type' => $admissionType,
        'student_surname' => $studentSurname,
        'student_other_names' => $studentOtherNames,
        'student_first_name' => $studentOtherNames,
        'student_last_name' => $studentSurname,
        'date_of_birth' => $dateOfBirth,
        'gender' => $gender,
        'nationality' => 'Ugandan',
        'religion' => $religion,
        'tribe' => $tribe,
        'position_in_family' => $positionInFamily,
        'has_attended_school' => $hasAttendedSchool,
        'previous_school_name' => $previousSchoolName,
        'previous_school_location' => $previousSchoolLoc,
        'language_1' => $language1,
        'language_2' => $language2,
        'responsible_person_name' => $responsibleName,
        'responsible_person_address' => $responsibleAddress,
        'responsible_person_phone' => $responsiblePhone,
        'responsible_person_email' => $responsibleEmail,
        'responsible_person_postal' => $responsiblePostal,
        'parent_first_name' => $responsibleName,
        'parent_last_name' => $fatherName ?: $responsibleName,
        'parent_phone' => $responsiblePhone,
        'parent_email' => $responsibleEmail,
        'parent_address' => $responsibleAddress,
        'parent_occupation' => $fatherOccupation ?: $motherOccupation,
        'parent_relationship' => 'Parent/Guardian',
        'parents_live_together' => $parentsLiveTogether,
        'father_name' => $fatherName ?: $responsibleName,
        'father_occupation' => $fatherOccupation,
        'father_workplace' => $fatherWorkplace,
        'father_address' => $fatherAddress,
        'mother_name' => $motherName,
        'mother_occupation' => $motherOccupation,
        'mother_workplace' => $motherWorkplace,
        'mother_address' => $motherAddress,
        'emergency_next_of_kin' => $emergencyNextOfKin,
        'emergency_contact_name' => $emergencyNextOfKin,
        'emergency_contact_phone' => $responsiblePhone,
        'emergency_contact_relationship' => 'Next of Kin',
        'bed_wetting' => $bedWetting,
        'has_health_handicap' => $hasHealthHandicap,
        'health_handicap_details' => $healthHandicapDetails,
        'doctor_name' => $doctorName,
        'doctor_location' => $doctorLocation,
        'is_immunized' => $isImmunized,
        'other_information' => $otherInformation,
        'rules_acknowledged' => $rulesAcknowledged,
        'declaration_accepted' => $declarationAccepted,
        'declaration_accepted_at' => $declarationDate,
        'declaration_name' => $declarationName,
        'declaration_version' => 'v1.0',
        'signature_path' => $relativeSigPath,
        'security_token' => $securityToken,
        'status' => 'pending',
        'form_fee_status' => 'unpaid'
    ];

    $colsToInsert = [];
    $placeholders = [];
    $execParams = [];

    foreach ($rowValues as $col => $val) {
        if (isset($existingCols[$col])) {
            $colsToInsert[] = "`{$col}`";
            $placeholders[] = '?';
            $execParams[] = $val;
        }
    }

    if (isset($existingCols['submitted_date'])) {
        $colsToInsert[] = "`submitted_date`";
        $placeholders[] = "NOW()";
    }

    $colSql = implode(', ', $colsToInsert);
    $valSql = implode(', ', $placeholders);

    $stmt = $db->prepare("INSERT INTO admission_applications ({$colSql}) VALUES ({$valSql})");
    $stmt->execute($execParams);
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
