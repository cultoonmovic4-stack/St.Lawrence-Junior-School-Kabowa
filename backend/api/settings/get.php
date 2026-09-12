<?php
/**
 * Get System Settings API Endpoint
 * St. Lawrence Junior School Kabowa
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Authenticate administrator session
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to access settings.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Query all settings from database
    $stmt = $db->query("SELECT setting_key, setting_value, setting_type, description FROM settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Default institutional settings fallback
    $defaults = [
        'theme_mode' => 'light',
        'sidebar_behavior' => 'expanded',
        'notifications_enabled' => '1',
        'confirm_dialogs' => '1',
        'auto_logout' => '30',
        'date_format' => 'DD MMM YYYY',
        'time_format' => '12h',
        'academic_year' => '2026',
        'current_term' => '1',
        'school_name' => 'St. Lawrence Junior School - Kabowa',
        'school_email' => 'stlawrencejuniorschoolkabowa@gmail.com',
        'school_phone' => '+256701420506',
        'school_address' => 'P.O.BOX 36198, KAMPALA, UGANDA'
    ];

    $settings = $defaults;
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    echo json_encode([
        'success' => true,
        'data' => $settings
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error retrieving settings: ' . $e->getMessage()
    ]);
}
