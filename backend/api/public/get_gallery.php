<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;

    $sql = "
        SELECT 
            id,
            id AS gallery_id,
            title,
            title AS image_title,
            description,
            description AS image_description,
            image_url,
            image_url AS image_path,
            category,
            upload_date,
            display_order,
            status
        FROM gallery_images
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

    $sql .= " ORDER BY display_order DESC, upload_date DESC, id DESC LIMIT :limit";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total' => count($images),
        'data' => $images
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
