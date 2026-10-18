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
    
    // 1. Inspect existing columns in admission_applications table
    $existingCols = [];
    try {
        $stmtCols = $db->query("SHOW COLUMNS FROM admission_applications");
        if ($stmtCols) {
            $existingCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (\Throwable $e) {
        $existingCols = [];
    }
    
    // 2. Self-healing database migration: ensure administrative office columns exist
    $colsToEnsure = [
        'form_fee_status' => "VARCHAR(50) NOT NULL DEFAULT 'unpaid'",
        'admitted_on' => "DATE DEFAULT NULL",
        'reported_on' => "DATE DEFAULT NULL"
    ];
    
    foreach ($colsToEnsure as $col => $def) {
        if (!empty($existingCols) && !in_array($col, $existingCols)) {
            try {
                $db->exec("ALTER TABLE admission_applications ADD COLUMN `{$col}` {$def}");
                $existingCols[] = $col;
            } catch (\Throwable $t) {
                // Continue gracefully if ALTER TABLE permission is restricted
            }
        }
    }
    
    // 3. Dynamically construct SELECT columns so SQL 1054 Unknown column can never occur
    $desiredFields = [
        'id',
        'application_reference',
        'academic_year',
        'term',
        'class_to_join',
        'admission_type',
        'student_surname',
        'student_other_names',
        'date_of_birth',
        'gender',
        'religion',
        'tribe',
        'position_in_family',
        'has_attended_school',
        'previous_school_name',
        'previous_school_location',
        'language_1',
        'language_2',
        'responsible_person_name',
        'responsible_person_address',
        'responsible_person_phone',
        'responsible_person_email',
        'responsible_person_postal',
        'parents_live_together',
        'father_name',
        'father_occupation',
        'father_workplace',
        'father_address',
        'mother_name',
        'mother_occupation',
        'mother_workplace',
        'mother_address',
        'emergency_next_of_kin',
        'bed_wetting',
        'has_health_handicap',
        'health_handicap_details',
        'doctor_name',
        'doctor_location',
        'is_immunized',
        'other_information',
        'rules_acknowledged',
        'declaration_accepted',
        'declaration_accepted_at',
        'declaration_name',
        'declaration_version',
        'signature_path',
        'security_token',
        'submitted_date',
        'status',
        'reviewed_by',
        'review_date',
        'review_notes',
        'form_fee_status',
        'admitted_on',
        'reported_on'
    ];
    
    $selectParts = [];
    if (!empty($existingCols)) {
        foreach ($desiredFields as $field) {
            if (in_array($field, $existingCols)) {
                $selectParts[] = "`{$field}`";
            } else {
                // Safe fallbacks for missing columns or schema differences
                if ($field === 'form_fee_status') {
                    $selectParts[] = "'unpaid' AS `form_fee_status`";
                } elseif ($field === 'student_surname' && in_array('student_last_name', $existingCols)) {
                    $selectParts[] = "`student_last_name` AS `student_surname`";
                } elseif ($field === 'student_other_names' && in_array('student_first_name', $existingCols)) {
                    $selectParts[] = "`student_first_name` AS `student_other_names`";
                } elseif ($field === 'application_reference' && in_array('application_id', $existingCols)) {
                    $selectParts[] = "`application_id` AS `application_reference`";
                } elseif ($field === 'responsible_person_phone' && in_array('parent_phone', $existingCols)) {
                    $selectParts[] = "`parent_phone` AS `responsible_person_phone`";
                } elseif ($field === 'responsible_person_email' && in_array('parent_email', $existingCols)) {
                    $selectParts[] = "`parent_email` AS `responsible_person_email`";
                } elseif ($field === 'responsible_person_address' && in_array('parent_address', $existingCols)) {
                    $selectParts[] = "`parent_address` AS `responsible_person_address`";
                } elseif ($field === 'responsible_person_name' && in_array('parent_first_name', $existingCols) && in_array('parent_last_name', $existingCols)) {
                    $selectParts[] = "CONCAT(COALESCE(`parent_first_name`,''), ' ', COALESCE(`parent_last_name`,'')) AS `responsible_person_name`";
                } else {
                    $selectParts[] = "NULL AS `{$field}`";
                }
            }
        }
        
        // Also include any legacy column fields if present so callers can access either format
        foreach (['application_id', 'student_first_name', 'student_last_name', 'parent_first_name', 'parent_last_name', 'parent_phone', 'parent_email'] as $legacyCol) {
            if (in_array($legacyCol, $existingCols) && !in_array("`{$legacyCol}`", $selectParts)) {
                $selectParts[] = "`{$legacyCol}`";
            }
        }
    } else {
        $selectParts[] = "*";
    }
    
    $orderClause = (in_array('submitted_date', $existingCols)) ? "ORDER BY submitted_date DESC" : "ORDER BY id DESC";
    $query = "SELECT " . implode(",\n            ", $selectParts) . " FROM admission_applications {$orderClause}";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rawApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Normalize and cross-alias all application records
    $applications = [];
    foreach ($rawApplications as $app) {
        // Cross-populate identifiers
        $ref = $app['application_reference'] ?? $app['application_id'] ?? ('APP-' . ($app['id'] ?? '0'));
        $app['application_reference'] = $ref;
        $app['application_id'] = $ref;
        
        // Cross-populate student names
        $surname = $app['student_surname'] ?? $app['student_last_name'] ?? '';
        $otherNames = $app['student_other_names'] ?? $app['student_first_name'] ?? '';
        $app['student_surname'] = $surname;
        $app['student_other_names'] = $otherNames;
        $app['student_first_name'] = $app['student_first_name'] ?? $surname;
        $app['student_last_name'] = $app['student_last_name'] ?? $otherNames;
        
        // Cross-populate responsible person / parent details
        $respName = $app['responsible_person_name'] ?? trim(($app['parent_first_name'] ?? '') . ' ' . ($app['parent_last_name'] ?? ''));
        if (empty($respName)) {
            $respName = $app['father_name'] ?? $app['mother_name'] ?? '';
        }
        $app['responsible_person_name'] = $respName;
        $app['parent_first_name'] = $app['parent_first_name'] ?? $respName;
        $app['parent_last_name'] = $app['parent_last_name'] ?? '';
        
        $app['responsible_person_phone'] = $app['responsible_person_phone'] ?? $app['parent_phone'] ?? '';
        $app['parent_phone'] = $app['parent_phone'] ?? $app['responsible_person_phone'];
        
        $app['responsible_person_email'] = $app['responsible_person_email'] ?? $app['parent_email'] ?? '';
        $app['parent_email'] = $app['parent_email'] ?? $app['responsible_person_email'];
        
        // Administrative defaults
        $app['form_fee_status'] = $app['form_fee_status'] ?? 'unpaid';
        $app['admitted_on'] = $app['admitted_on'] ?? null;
        $app['reported_on'] = $app['reported_on'] ?? null;
        $app['status'] = $app['status'] ?? 'pending';
        
        $applications[] = $app;
    }
    
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

