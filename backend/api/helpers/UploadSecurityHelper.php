<?php
/**
 * St. Lawrence Junior School - Secure Upload Handler
 * Centralized, multi-layered file upload validation and storage utility
 * 
 * Features:
 * - Server-side MIME type detection via finfo_file() (ignores client Content-Type)
 * - Deep content inspection (getimagesize, magic byte verification, PHP polyglot check)
 * - Strict category-based allowlists for MIME types and canonical extensions
 * - Cryptographically random filename generation (random_bytes)
 * - Strict rejection of executable and script extensions
 * - File size enforcement per category
 * - Path traversal prevention
 */

class UploadSecurityHelper {

    // Upload Categories
    const CATEGORY_IMAGE = 'image';
    const CATEGORY_ADMISSION_DOCUMENT = 'admission_document';
    const CATEGORY_LIBRARY = 'library';

    // Maximum file sizes in bytes
    const MAX_SIZE_IMAGE = 5242880; // 5 MB
    const MAX_SIZE_ADMISSION_DOCUMENT = 10485760; // 10 MB
    const MAX_SIZE_LIBRARY = 26214400; // 25 MB

    // Dangerous extensions that must never be accepted under any circumstance
    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',
        'cgi', 'pl', 'py', 'pyc', 'pyo', 'sh', 'bash', 'zsh', 'ksh', 'csh',
        'exe', 'bat', 'cmd', 'com', 'msi', 'vbs', 'vbe', 'wsf', 'wsh', 'ps1',
        'js', 'jsp', 'jspx', 'asp', 'aspx', 'cer', 'asa', 'shtml', 'htm', 'html',
        'svg', 'xml', 'htaccess', 'htpasswd', 'ini', 'conf', 'config', 'dll'
    ];

    // Allowed MIME types mapped to canonical extension per category
    private static $allowedMimeMap = [
        self::CATEGORY_IMAGE => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ],
        self::CATEGORY_ADMISSION_DOCUMENT => [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp'
        ],
        self::CATEGORY_LIBRARY => [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'video/mp4'  => 'mp4'
        ]
    ];

    /**
     * Validates and securely saves an uploaded file.
     *
     * @param array  $file           The $_FILES['key'] entry
     * @param string $category       One of the CATEGORY_* constants
     * @param string $destinationDir Absolute or relative path to target directory
     * @param string $prefix         Optional prefix for generated filename
     * @return array [
     *     'success'   => bool,
     *     'filename'  => string, // Random generated filename (e.g. passport_abc123.jpg)
     *     'filepath'  => string, // Full target path
     *     'mime'      => string, // Validated MIME type
     *     'extension' => string, // Canonical extension
     *     'size'      => int     // File size in bytes
     * ]
     * @throws Exception with safe, user-facing error message
     */
    public static function validateAndSave(array $file, string $category, string $destinationDir, string $prefix = ''): array {
        // 1. Basic $_FILES structure validation
        if (!isset($file['error']) || !isset($file['tmp_name']) || !isset($file['name']) || !isset($file['size'])) {
            throw new Exception('Invalid upload payload.');
        }

        // 2. Check PHP upload error code
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file was uploaded.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds server upload limit.');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File was only partially uploaded.');
            default:
                throw new Exception('Upload failed due to a system error.');
        }

        // 3. Verify actual HTTP uploaded file
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Security violation: file is not a valid upload.');
        }

        // 4. Validate category
        if (!isset(self::$allowedMimeMap[$category])) {
            throw new Exception('Invalid upload category configuration.');
        }

        // 5. Enforce Category Size Limits
        $maxSize = self::getMaxSizeForCategory($category);
        if ($file['size'] > $maxSize || filesize($file['tmp_name']) > $maxSize) {
            $maxMb = round($maxSize / (1024 * 1024));
            throw new Exception("File size exceeds the allowed limit of {$maxMb}MB.");
        }
        if ($file['size'] === 0 || filesize($file['tmp_name']) === 0) {
            throw new Exception('Uploaded file is empty.');
        }

        // 6. Inspect Original Filename for Dangerous Extension / Double-Extension Tricks
        $originalName = $file['name'];
        self::checkDangerousOriginalFilename($originalName);

        // 7. Server-Side MIME Type Detection (Using finfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            throw new Exception('Unable to initialize MIME detection engine.');
        }
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedCategoryMimes = self::$allowedMimeMap[$category];
        if (!array_key_exists($detectedMime, $allowedCategoryMimes)) {
            throw new Exception('Invalid or unsupported file type.');
        }

        // 8. Deep Content Inspection
        self::inspectFileContent($file['tmp_name'], $detectedMime);

        // 9. Derive Safe Canonical Extension Strictly From Validated MIME Type
        $canonicalExtension = $allowedCategoryMimes[$detectedMime];

        // 10. Generate Cryptographically Secure Random Filename
        $randomToken = bin2hex(random_bytes(12));
        $cleanPrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '', $prefix);
        if ($cleanPrefix !== '') {
            $generatedFilename = $cleanPrefix . '_' . time() . '_' . $randomToken . '.' . $canonicalExtension;
        } else {
            $generatedFilename = time() . '_' . $randomToken . '.' . $canonicalExtension;
        }

        // 11. Ensure Destination Directory Exists with Safe Permissions
        if (!file_exists($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                throw new Exception('Failed to initialize destination storage directory.');
            }
        }

        $realDestDir = realpath($destinationDir);
        if (!$realDestDir || !is_dir($realDestDir)) {
            throw new Exception('Storage directory path could not be resolved.');
        }

        $targetFilePath = $realDestDir . DIRECTORY_SEPARATOR . $generatedFilename;

        // 12. Move Uploaded File to Target Storage
        if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            throw new Exception('Failed to safely store the uploaded file.');
        }

        // 13. Set Non-Executable File Permissions
        @chmod($targetFilePath, 0644);

        return [
            'success'   => true,
            'filename'  => $generatedFilename,
            'filepath'  => $targetFilePath,
            'mime'      => $detectedMime,
            'extension' => $canonicalExtension,
            'size'      => $file['size']
        ];
    }

    /**
     * Inspect original filename to reject double extensions or null bytes.
     */
    private static function checkDangerousOriginalFilename(string $filename): void {
        // Check for null byte injection
        if (strpos($filename, "\0") !== false) {
            throw new Exception('Invalid filename: null byte sequence detected.');
        }

        // Check for directory traversal sequences
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            throw new Exception('Invalid filename: path traversal sequence detected.');
        }

        // Split filename by dots and check all extension parts against dangerous list
        $parts = explode('.', $filename);
        if (count($parts) > 1) {
            // Remove name part
            array_shift($parts);
            foreach ($parts as $part) {
                $lowerPart = strtolower(trim($part));
                if (in_array($lowerPart, self::$dangerousExtensions, true)) {
                    throw new Exception('Invalid file: prohibited extension detected.');
                }
            }
        }
    }

    /**
     * Inspect file content based on MIME type to prevent polyglots and fake headers.
     */
    private static function inspectFileContent(string $tmpPath, string $mime): void {
        // Image validation
        if (strpos($mime, 'image/') === 0) {
            $imageInfo = @getimagesize($tmpPath);
            if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
                throw new Exception('File content is not a valid image.');
            }

            $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
            if (defined('IMAGETYPE_WEBP')) {
                $allowedImageTypes[] = IMAGETYPE_WEBP;
            }

            if (!in_array($imageInfo[2], $allowedImageTypes, true)) {
                throw new Exception('Unsupported image format structure.');
            }
        }

        // PDF validation: Check '%PDF-' magic bytes at beginning of file
        if ($mime === 'application/pdf') {
            $handle = @fopen($tmpPath, 'rb');
            if ($handle) {
                $header = fread($handle, 5);
                fclose($handle);
                if ($header !== '%PDF-') {
                    throw new Exception('File header does not match valid PDF format.');
                }
            } else {
                throw new Exception('Failed to inspect document content.');
            }
        }

        // Polyglot check: Ensure no embedded PHP execution tags exist inside images or documents
        $sampleHandle = @fopen($tmpPath, 'rb');
        if ($sampleHandle) {
            // Read first 8KB and last 8KB of file to check for embedded script tags
            $headContent = fread($sampleHandle, 8192);
            $fileSize = filesize($tmpPath);
            $tailContent = '';
            if ($fileSize > 8192) {
                fseek($sampleHandle, -8192, SEEK_END);
                $tailContent = fread($sampleHandle, 8192);
            }
            fclose($sampleHandle);

            $combined = strtolower($headContent . $tailContent);
            if (strpos($combined, '<?php') !== false || 
                strpos($combined, '<?=') !== false || 
                strpos($combined, '<script') !== false) {
                throw new Exception('Security violation: malicious script signature detected in file content.');
            }
        }
    }

    /**
     * Returns max size in bytes for a given category.
     */
    private static function getMaxSizeForCategory(string $category): int {
        switch ($category) {
            case self::CATEGORY_IMAGE:
                return self::MAX_SIZE_IMAGE;
            case self::CATEGORY_ADMISSION_DOCUMENT:
                return self::MAX_SIZE_ADMISSION_DOCUMENT;
            case self::CATEGORY_LIBRARY:
                return self::MAX_SIZE_LIBRARY;
            default:
                return self::MAX_SIZE_IMAGE;
        }
    }
}
