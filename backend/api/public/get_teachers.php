<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $sql = "
        SELECT 
            id,
            id as teacher_id,
            full_name,
            full_name as name,
            email,
            phone,
            department,
            position,
            specialization,
            specialization as subject_specialization,
            specialization as specialties,
            qualification,
            experience_years,
            photo_url,
            photo_url as profile_photo,
            bio,
            status,
            display_order
        FROM teachers
        WHERE status = 'active'
    ";
    
    if ($id > 0) {
        $sql .= " AND id = :id";
    } elseif ($department && $department !== 'all') {
        $sql .= " AND department = :department";
    }
    
    $sql .= " ORDER BY display_order DESC, full_name ASC";
    
    $stmt = $db->prepare($sql);
    
    if ($id > 0) {
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    } elseif ($department && $department !== 'all') {
        $stmt->bindParam(':department', $department);
    }
    
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($id > 0) {
        $teacher = count($teachers) > 0 ? $teachers[0] : null;
        echo json_encode([
            'success' => $teacher !== null,
            'data' => $teacher,
            'message' => $teacher ? 'Teacher found' : 'Teacher not found'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $teachers
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
