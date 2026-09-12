<?php
/**
 * St. Lawrence Junior School Kabowa
 * Secure Application Status Lookup
 * Requires both Application Reference and Registered Telephone to prevent applicant enumeration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/Database.php';

// Rate limiting for failed attempts (max 5 failed attempts in 10 minutes per IP)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitDir = sys_get_temp_dir() . '/sljk_rate_limits/';
if (!file_exists($rateLimitDir)) {
    @mkdir($rateLimitDir, 0755, true);
}
$rateLimitFile = $rateLimitDir . 'status_' . md5($clientIp) . '.json';
$now = time();
$attempts = [];
if (file_exists($rateLimitFile)) {
    $attempts = json_decode(file_get_contents($rateLimitFile), true) ?? [];
    // Filter attempts within last 600 seconds (10 minutes)
    $attempts = array_filter($attempts, function($t) use ($now) { return ($now - $t) < 600; });
}

if (count($attempts) >= 5) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many failed verification attempts. Please wait 10 minutes before trying again.'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$inputJson = json_decode($rawInput, true) ?? [];
$ref = trim($_REQUEST['ref'] ?? $_REQUEST['reference'] ?? $_REQUEST['application_reference'] ?? $inputJson['ref'] ?? $inputJson['reference'] ?? $inputJson['application_reference'] ?? '');
$phone = trim($_REQUEST['phone'] ?? $_REQUEST['responsible_person_phone'] ?? $inputJson['phone'] ?? $inputJson['responsible_person_phone'] ?? '');

if (empty($ref) || empty($phone)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Both Application Reference and Registered Telephone Number are required.'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Clean phone number for resilient matching
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) >= 7) {
        $last7 = substr($cleanPhone, -7);
    } else {
        $last7 = $cleanPhone;
    }

    $stmt = $db->prepare("
        SELECT 
            application_reference,
            student_surname,
            student_other_names,
            class_to_join,
            academic_year,
            term,
            admission_type,
            status,
            submitted_date,
            form_fee_status,
            security_token
        FROM admission_applications 
        WHERE application_reference = :ref 
          AND (responsible_person_phone LIKE :phone_exact OR responsible_person_phone LIKE :phone_suffix)
        LIMIT 1
    ");

    $phoneSuffix = '%' . $last7;
    $stmt->bindParam(':ref', $ref);
    $stmt->bindParam(':phone_exact', $phone);
    $stmt->bindParam(':phone_suffix', $phoneSuffix);
    $stmt->execute();

    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        $attempts[] = time();
        @file_put_contents($rateLimitFile, json_encode($attempts));
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No application found matching the provided reference and telephone number.'
        ]);
        exit;
    }

    // Clear failed attempts on successful verification
    if (file_exists($rateLimitFile)) {
        @unlink($rateLimitFile);
    }

    // Mask student name for privacy (e.g. Kato J****)
    $maskedName = $app['student_surname'] . ' ' . substr($app['student_other_names'], 0, 1) . '****';

    echo json_encode([
        'success' => true,
        'application' => [
            'reference' => $app['application_reference'],
            'child_name' => $maskedName,
            'class' => $app['class_to_join'],
            'academic_year' => $app['academic_year'],
            'term' => $app['term'],
            'admission_type' => ucfirst($app['admission_type']),
            'status' => $app['status'],
            'submitted_date' => $app['submitted_date'],
            'fee_status' => ucfirst($app['form_fee_status']),
            'download_token' => $app['security_token']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to check application status at this time.'
    ]);
}
