<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $sql = "
        SELECT 
            id,
            id AS book_id,
            title,
            title AS book_title,
            description,
            category,
            class_level,
            subject,
            file_url,
            file_url AS pdf_path,
            file_type,
            file_size,
            download_count,
            upload_date,
            created_at,
            status
        FROM library_resources
        WHERE status = 'active'
    ";
    
    $params = [];
    if (!empty($category) && $category !== 'all') {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }
    
    if ($id > 0) {
        $sql .= " AND id = :id";
        $params[':id'] = $id;
    }
    
    $sql .= " ORDER BY id DESC LIMIT :limit";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => count($resources),
        'data' => $resources
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
