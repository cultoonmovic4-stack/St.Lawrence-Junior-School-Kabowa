<?php
/**
 * St. Lawrence Junior School Kabowa
 * Secure PDF Download / View Controller
 * Serves submitted application dossier in official PDF format
 */

error_reporting(0);
ini_set('display_errors', 0);

// Buffer all output to guarantee zero stray bytes, warnings, or BOM before PDF binary stream
ob_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/PDFGenerator.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';
require_once __DIR__ . '/../middleware/permission_middleware.php';

SessionHelper::start();

$type = $_GET['type'] ?? '';
$ref = $_GET['ref'] ?? '';
$token = $_GET['token'] ?? '';
$id = $_GET['id'] ?? null;

// Allow downloading blank form directly if requested with type=blank
if ($type === 'blank') {
    try {
        $pdf = new AdmissionPDFGenerator(null, true);
        $pdf->generateBlank();
        $pdfData = $pdf->Output('S');

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($pdfData));
        header('Content-Disposition: inline; filename="SLJK_Blank_Admission_Application_Form.pdf"');
        header('Cache-Control: public, max-age=3600');
        header('Pragma: public');

        echo $pdfData;
        exit;
    } catch (Exception $e) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code(500);
        die('Failed to generate blank application document.');
    }
}

if (empty($ref) && empty($id)) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(400);
    die('Application reference or ID is required.');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!empty($ref)) {
        $stmt = $db->prepare("SELECT * FROM admission_applications WHERE application_reference = :ref LIMIT 1");
        $stmt->bindParam(':ref', $ref);
        $stmt->execute();
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare("SELECT * FROM admission_applications WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$app) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code(404);
        die('Application not found.');
    }

    // Access authorization check:
    // Case 1: Matching security_token provided (Public applicant post-submission)
    $isTokenValid = (!empty($token) && hash_equals($app['security_token'], $token));
    
    // Case 2: Logged-in admin user with admission.view permission
    $isAdminAuthorized = (isAuthenticated() && hasPermission('admission.view'));

    if (!$isTokenValid && !$isAdminAuthorized) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code(403);
        die('Access denied. Valid security token or administrative credentials required.');
    }

    $pdf = new AdmissionPDFGenerator($app, false);
    $pdf->generateSubmitted();
    $cleanRef = preg_replace('/[^a-zA-Z0-9_-]/', '', $app['application_reference']);
    $filename = "SLJK_Application_{$cleanRef}.pdf";

    $pdfData = $pdf->Output('S');

    // Flush all output buffers completely
    while (ob_get_level()) {
        ob_end_clean();
    }

    $isDownload = isset($_GET['dl']) || isset($_GET['download']);
    $disposition = $isDownload ? 'attachment' : 'inline';

    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdfData));
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdfData;
    exit;

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    error_log("PDF Generation Error: " . $e->getMessage());
    http_response_code(500);
    die('Failed to generate application document: ' . $e->getMessage());
}
