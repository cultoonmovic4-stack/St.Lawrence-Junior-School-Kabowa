<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';
require_once '../helpers/UploadSecurityHelper.php';

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permission
requirePermission('user.edit');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check which password column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'password%'");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $passwordColumn = in_array('password', $columns) ? 'password' : 'password_hash';
    
    $userId = $_POST['user_id'] ?? null;
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $roleId = $_POST['role_id'] ?? null;
    $status = $_POST['status'] ?? 'active';
    
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    // Check if username or email already exists for other users
    $stmt = $db->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :user_id");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Username or email already exists');
    }
    
    // Handle profile image upload securely
    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadResult = UploadSecurityHelper::validateAndSave(
            $_FILES['profile_image'],
            UploadSecurityHelper::CATEGORY_IMAGE,
            $uploadDir,
            'profile'
        );
        $profileImage = 'backend/uploads/profiles/' . $uploadResult['filename'];
    }
    
    // Build update query
    $updateFields = [
        'username = :username',
        'email = :email',
        'full_name = :full_name',
        'phone = :phone',
        'role_id = :role_id',
        'status = :status'
    ];
    
    $params = [
        ':username' => $username,
        ':email' => $email,
        ':full_name' => $fullName,
        ':phone' => $phone,
        ':role_id' => $roleId,
        ':status' => $status,
        ':user_id' => $userId
    ];
    
    // Add password if provided
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "{$passwordColumn} = :password";
        $params[':password'] = $hashedPassword;
    }
    
    // Add profile image if uploaded
    if ($profileImage) {
        $updateFields[] = 'profile_image = :profile_image';
        $params[':profile_image'] = $profileImage;
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully!'
        ]);
    } else {
        throw new Exception('Failed to update user');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
