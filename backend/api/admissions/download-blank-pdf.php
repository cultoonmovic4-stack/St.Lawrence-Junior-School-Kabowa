<?php
/**
 * St. Lawrence Junior School Kabowa
 * Dedicated Blank Application Form PDF Controller
 * Serves printable blank admission application form matching current online structure
 */

error_reporting(0);
ini_set('display_errors', 0);

ob_start();

require_once __DIR__ . '/../helpers/PDFGenerator.php';

try {
    $pdf = new AdmissionPDFGenerator(null, true);
    $pdf->generateBlank();
    $pdfData = $pdf->Output('S');

    while (ob_get_level()) {
        ob_end_clean();
    }

    $isDownload = isset($_GET['dl']) || isset($_GET['download']);
    $disposition = $isDownload ? 'attachment' : 'inline';

    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdfData));
    header('Content-Disposition: ' . $disposition . '; filename="SLJK_Blank_Admission_Application_Form.pdf"');
    header('Cache-Control: public, max-age=86400');
    header('Pragma: public');

    echo $pdfData;
    exit;
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    error_log("Blank PDF Generation Error: " . $e->getMessage());
    http_response_code(500);
    die('Failed to generate blank application document.');
}
