<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'reading';
    $class_level = $_POST['class_level'] ?? 'all';
    $subject = $_POST['subject'] ?? '';
    $upload_date = $_POST['upload_date'] ?? date('Y-m-d');
    
    // Validate category if provided
    if (!empty($category)) {
        $validCategories = ['assignment', 'reading', 'past_exam', 'revision', 'multimedia', 'study_guide'];
        if (!in_array($category, $validCategories)) {
            throw new Exception('Invalid category');
        }
    }
    
    // Validate class level if provided
    if (!empty($class_level)) {
        $validLevels = ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'all'];
        if (!in_array($class_level, $validLevels)) {
            throw new Exception('Invalid class level');
        }
    }
    
    // Handle file upload securely with clear diagnostics
    if (!isset($_FILES['file'])) {
        throw new Exception('No file received by server. File may exceed maximum POST size.');
    }
    
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File is too large for server limits (upload_max_filesize).');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('Upload was interrupted. Please try again.');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file was selected for upload.');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Server temporary upload folder is missing.');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Server failed to write file to disk.');
            default:
                throw new Exception('Upload failed with PHP system code: ' . $_FILES['file']['error']);
        }
    }
    
    $uploadDir = __DIR__ . '/../../uploads/library/';
    if (!file_exists($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    
    $uploadResult = UploadSecurityHelper::validateAndSave(
        $_FILES['file'],
        UploadSecurityHelper::CATEGORY_LIBRARY,
        $uploadDir,
        'library'
    );
    
    // Auto-generate title from filename if not provided
    if (empty($title)) {
        $originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
        $title = ucwords(str_replace(['_', '-'], ' ', $originalName));
    }
    
    // Save to database
    $database = new Database();
    $db = $database->getConnection();
    
    $currentUser = getCurrentUser();
    $userId = (!empty($currentUser['user_id']) && is_numeric($currentUser['user_id'])) ? (int)$currentUser['user_id'] : null;
    if ($userId !== null) {
        $checkUser = $db->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
        $checkUser->execute([':id' => $userId]);
        if (!$checkUser->fetch()) {
            $userId = null;
        }
    }
    
    $fileUrl = 'uploads/library/' . $uploadResult['filename'];
    $fileType = $uploadResult['extension'];
    $fileSize = $uploadResult['size'];
    
    $stmt = $db->prepare("
        INSERT INTO library_resources 
        (title, description, category, class_level, subject, file_url, file_type, file_size, uploaded_by, upload_date, status) 
        VALUES 
        (:title, :description, :category, :class_level, :subject, :file_url, :file_type, :file_size, :uploaded_by, :upload_date, 'active')
    ");
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':class_level', $class_level);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':file_url', $fileUrl);
    $stmt->bindParam(':file_type', $fileType);
    $stmt->bindParam(':file_size', $fileSize);
    $stmt->bindValue(':uploaded_by', $userId, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':upload_date', $upload_date);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Resource uploaded successfully!',
            'data' => [
                'id' => $db->lastInsertId(),
                'title' => $title,
                'file_url' => $fileUrl
            ]
        ]);
    } else {
        throw new Exception('Failed to save to database');
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
