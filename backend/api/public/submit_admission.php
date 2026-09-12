<?php
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => false,
    'message' => 'This legacy endpoint has been deprecated. All admission applications are processed through /backend/api/admissions/create-public-with-files.php'
]);
exit;
