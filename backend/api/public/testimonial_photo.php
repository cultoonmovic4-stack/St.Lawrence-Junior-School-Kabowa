<?php
// Serves a testimonial photo stored in the database.
require_once '../config/Database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $has = $db->query("SHOW COLUMNS FROM testimonials LIKE 'photo_data'")->fetch();
    if (!$has) {
        http_response_code(404);
        exit;
    }

    $stmt = $db->prepare("SELECT photo_data, photo_mime FROM testimonials WHERE id = :id AND status = 'approved'");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['photo_data'])) {
        http_response_code(404);
        exit;
    }

    $mime = in_array($row['photo_mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)
        ? $row['photo_mime'] : 'image/jpeg';

    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . strlen($row['photo_data']));
    echo $row['photo_data'];
} catch (Exception $e) {
    http_response_code(500);
}
