<?php
/**
 * Update System Settings API Endpoint
 * St. Lawrence Junior School Kabowa
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Authenticate session
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
    exit;
}

// Ensure user has administrative privileges (role level >= 80)
$currentUser = getCurrentUser();
$roleLevel = $_SESSION['role_level'] ?? 0;
if ($roleLevel < 80 && !hasRoleLevel(80)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to modify system settings.']);
    exit;
}

// Retrieve input data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input && !empty($_POST)) {
    $input = $_POST;
}

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No settings data provided.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $userId = $currentUser['user_id'] ?? $_SESSION['user_id'] ?? null;

    // Supported system settings configuration definition
    $allowedSettings = [
        'theme_mode' => [
            'type' => 'text',
            'desc' => 'Dashboard interface theme mode (light or dark)',
            'valid' => function($val) { return in_array($val, ['light', 'dark']); }
        ],
        'sidebar_behavior' => [
            'type' => 'text',
            'desc' => 'Admin sidebar navigation display behavior',
            'valid' => function($val) { return in_array($val, ['expanded', 'collapsed', 'auto']); }
        ],
        'notifications_enabled' => [
            'type' => 'boolean',
            'desc' => 'Enable or disable administration notifications',
            'valid' => function($val) { return in_array((string)$val, ['0', '1', 'true', 'false']); }
        ],
        'confirm_dialogs' => [
            'type' => 'boolean',
            'desc' => 'Prompt confirmation dialogs before critical actions',
            'valid' => function($val) { return in_array((string)$val, ['0', '1', 'true', 'false']); }
        ],
        'auto_logout' => [
            'type' => 'number',
            'desc' => 'Inactivity auto-logout timeout in minutes (0 for disabled)',
            'valid' => function($val) { return is_numeric($val) && in_array((int)$val, [0, 15, 30, 60, 120]); }
        ],
        'date_format' => [
            'type' => 'text',
            'desc' => 'System date presentation format',
            'valid' => function($val) { return in_array($val, ['DD MMM YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD', 'd M Y', 'Y-m-d']); }
        ],
        'time_format' => [
            'type' => 'text',
            'desc' => 'System time presentation format (12h or 24h)',
            'valid' => function($val) { return in_array($val, ['12h', '24h']); }
        ],
        'academic_year' => [
            'type' => 'number',
            'desc' => 'Current academic year',
            'valid' => function($val) { return is_numeric($val) && (int)$val >= 2020 && (int)$val <= 2050; }
        ],
        'current_term' => [
            'type' => 'number',
            'desc' => 'Current academic term (1, 2, or 3)',
            'valid' => function($val) { return in_array((string)$val, ['1', '2', '3']); }
        ]
    ];

    $db->beginTransaction();

    $upsertStmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value, setting_type, description, updated_by)
        VALUES (:key, :val, :type, :desc, :user_id)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_by = VALUES(updated_by),
            updated_at = CURRENT_TIMESTAMP()
    ");

    $updatedCount = 0;
    foreach ($input as $key => $value) {
        if (!isset($allowedSettings[$key])) {
            continue; // Skip unrecognized settings
        }

        $config = $allowedSettings[$key];

        // Format boolean values
        if ($config['type'] === 'boolean') {
            $value = ($value === '1' || $value === 1 || $value === true || $value === 'true') ? '1' : '0';
        } else {
            $value = trim((string)$value);
        }

        // Validate value
        if (!$config['valid']($value)) {
            $db->rollBack();
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => "Invalid value provided for setting '{$key}'."
            ]);
            exit;
        }

        $upsertStmt->execute([
            ':key' => $key,
            ':val' => $value,
            ':type' => $config['type'],
            ':desc' => $config['desc'],
            ':user_id' => $userId
        ]);
        $updatedCount++;
    }

    $db->commit();

    // Log admin activity if logs table is present
    try {
        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, ip_address, created_at)
            VALUES (:user_id, 'settings_update', 'Updated administration system settings', :ip, NOW())
        ");
        $logStmt->execute([
            ':user_id' => $userId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $logEx) {
        // Logging table failure is non-fatal
    }

    echo json_encode([
        'success' => true,
        'message' => 'System settings have been successfully saved.',
        'updated_count' => $updatedCount
    ]);

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving system settings: ' . $e->getMessage()
    ]);
}
