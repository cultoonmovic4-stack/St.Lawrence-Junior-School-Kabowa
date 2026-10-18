<?php
/**
 * St. Lawrence Junior School Kabowa
 * Authoritative Admission Parent Email Notification Service
 * 
 * Reuses existing PHPMailer infrastructure and SMTP configuration.
 * Embeds official school logo (/img/5.jpg) via CID (cid:school_logo).
 * Strictly adheres to school branding: Deep Blue (#0B2545), Crimson (#C9182B), Motto "WE STRIVE TO EXCEL".
 * Includes duplicate notification prevention and non-disruptive error isolation.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AdmissionEmailService {

    /**
     * Canonical status list that triggers parent notification
     */
    const NOTIFIABLE_STATUSES = ['under_review', 'accepted', 'waitlist', 'rejected'];

    /**
     * Ensure notification tables exist in the database (self-healing)
     */
    public static function ensureTableExists($db) {
        static $tablesChecked = false;
        if ($tablesChecked) {
            return;
        }
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `admission_email_notifications` (
                    `id` INT PRIMARY KEY AUTO_INCREMENT,
                    `application_id` INT NOT NULL,
                    `application_reference` VARCHAR(50) NOT NULL,
                    `recipient_email` VARCHAR(255) NOT NULL,
                    `status_transition` VARCHAR(50) NOT NULL,
                    `email_status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
                    `error_message` TEXT DEFAULT NULL,
                    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_app_status` (`application_id`, `status_transition`),
                    INDEX `idx_app_ref` (`application_reference`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $db->exec("
                CREATE TABLE IF NOT EXISTS `email_logs` (
                    `id` INT PRIMARY KEY AUTO_INCREMENT,
                    `recipient_email` VARCHAR(255) NOT NULL,
                    `subject` VARCHAR(255) NOT NULL,
                    `message` TEXT DEFAULT NULL,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'sent',
                    `error_message` TEXT DEFAULT NULL,
                    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_recipient_email` (`recipient_email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $tablesChecked = true;
        } catch (\Throwable $t) {
            error_log("Failed to auto-create email notification tables: " . $t->getMessage());
        }
    }

    /**
     * Send admission status notification email
     * 
     * @param PDO $db Active PDO database connection
     * @param int $applicationId Database ID of the application
     * @param string $newStatus The new status to notify
     * @param string $previousStatus The previous status before transition
     * @param string $reviewNotes Optional administrative notes
     * @return array [email_sent => bool, message => string]
     */
    public static function sendStatusNotification($db, $applicationId, $newStatus, $previousStatus = null, $reviewNotes = '') {
        $canonicalStatus = strtolower(trim($newStatus));
        if ($canonicalStatus === 'waitlisted') {
            $canonicalStatus = 'waitlist';
        }

        // 1. Validate status is notifiable
        if (!in_array($canonicalStatus, self::NOTIFIABLE_STATUSES)) {
            return [
                'email_sent' => false,
                'message' => "Status '{$canonicalStatus}' does not require parent email notification."
            ];
        }

        // 2. Rely on the 2-minute deduplication window (Step 5) to prevent spamming while allowing admin to re-send/confirm

        // 3. Fetch full application record
        $stmt = $db->prepare("SELECT * FROM admission_applications WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return [
                'email_sent' => false,
                'message' => "Application ID {$applicationId} not found."
            ];
        }

        // Resolve recipient email across all possible fields in the application record
        $recipientEmail = '';
        foreach (['responsible_person_email', 'parent_email', 'emergency_next_of_kin_email', 'email'] as $emKey) {
            if (!empty($app[$emKey]) && filter_var(trim($app[$emKey]), FILTER_VALIDATE_EMAIL)) {
                $recipientEmail = trim($app[$emKey]);
                break;
            }
        }

        $appRef         = $app['application_reference'] ?? $app['application_id'] ?? ('SLJK-' . $app['id']);
        $studentName    = trim(($app['student_surname'] ?? $app['student_last_name'] ?? '') . ' ' . ($app['student_other_names'] ?? $app['student_first_name'] ?? ''));
        $classToJoin    = $app['class_to_join'] ?? 'General';
        $submissionDate = !empty($app['submitted_date']) ? date('d M Y', strtotime($app['submitted_date'])) : date('d M Y');
        $parentName     = $app['responsible_person_name'] ?? $app['parent_first_name'] ?? 'Parent / Guardian';

        // 4. Validate recipient email
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Admission Email Skipped: No valid email for application {$appRef}");
            return [
                'email_sent' => false,
                'message' => "No valid parent email recorded for this application."
            ];
        }

        // 5. Deduplication check: Only prevent duplicate if sent within the last 2 minutes
        self::ensureTableExists($db);
        try {
            $dedupStmt = $db->prepare("
                SELECT id, sent_at 
                FROM admission_email_notifications 
                WHERE application_id = :app_id 
                  AND status_transition = :status 
                  AND email_status = 'sent' 
                  AND sent_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                LIMIT 1
            ");
            $dedupStmt->execute([
                ':app_id' => $applicationId,
                ':status' => $canonicalStatus
            ]);
            $existingNotification = $dedupStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingNotification) {
                error_log("Duplicate Notification Prevented: {$canonicalStatus} email already sent to {$recipientEmail} for ref {$appRef} at {$existingNotification['sent_at']}");
                return [
                    'email_sent' => false,
                    'message' => "Duplicate notification prevented: {$canonicalStatus} email was already sent a moment ago."
                ];
            }
        } catch (\Throwable $dedupEx) {
            error_log("Deduplication check ignored: " . $dedupEx->getMessage());
        }

        // 6. Build status-specific email content
        $emailContent = self::buildContent($canonicalStatus, [
            'parent_name' => $parentName,
            'student_name' => $studentName,
            'class_to_join' => $classToJoin,
            'application_reference' => $appRef,
            'submitted_date' => $submissionDate,
            'review_notes' => $reviewNotes
        ]);

        // 7. Compile HTML template
        $statusCheckUrl = self::getStatusCheckUrl();
        $htmlBody = self::buildHtmlTemplate($emailContent, $statusCheckUrl);
        $textAltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], "\n", $htmlBody));

        // 8. Send email via HTTPS Relay (Port 443 — bypasses Railway outbound SMTP port 465/587 block), with PHPMailer SMTP fallback
        $mailSuccess = false;
        $errorMessage = null;

        $fromEmail = env('SMTP_FROM_EMAIL', 'stlawrencejuniorschoolkabowa@gmail.com');
        $fromName  = env('SMTP_FROM_NAME', 'St. Lawrence Junior School Kabowa');
        $logoPath  = realpath(__DIR__ . '/../../../img/5.jpg');

        $relayResult = self::sendViaHttpsRelay(
            $recipientEmail,
            $emailContent['subject'],
            $htmlBody,
            $textAltBody,
            $fromName,
            $fromEmail,
            $logoPath
        );

        if ($relayResult['success']) {
            $mailSuccess = true;
            $errorMessage = null;
            error_log("Admission notification email sent successfully via HTTPS relay to {$recipientEmail} for ref {$appRef} (Status: {$canonicalStatus})");
        } else {
            $errorMessage = $relayResult['error'] ?? 'HTTPS relay failed';
            error_log("HTTPS relay attempt note for ref {$appRef}: {$errorMessage}. Falling back to direct SMTP...");

            // Fallback to direct PHPMailer SMTP (for local XAMPP or unrestricted hosts)
            $configuredPort = (int)env('SMTP_PORT', 465);
            $attempts = ($configuredPort === 587)
                ? [
                    ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS],
                    ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS]
                ]
                : [
                    ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
                    ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS]
                ];

            foreach ($attempts as $attempt) {
                try {
                    $mailer = new PHPMailer(true);
                    $mailer->SMTPDebug = 0;
                    $mailer->isSMTP();
                    $mailer->Host = env('SMTP_HOST', 'smtp.gmail.com');
                    $mailer->SMTPAuth = true;
                    $mailer->Username = env('SMTP_USERNAME', 'stlawrencejuniorschoolkabowa@gmail.com');
                    $mailer->Password = env('SMTP_PASSWORD', 'rlexmpxeyykmgzbv');
                    $mailer->SMTPSecure = $attempt['secure'];
                    $mailer->Port = $attempt['port'];
                    $mailer->CharSet = 'UTF-8';
                    $mailer->Timeout = 6;

                    $mailer->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    $mailer->setFrom($fromEmail, $fromName);
                    $mailer->addAddress($recipientEmail, $parentName);
                    $mailer->Subject = $emailContent['subject'];
                    $mailer->isHTML(true);

                    if ($logoPath && file_exists($logoPath)) {
                        try {
                            $mailer->addEmbeddedImage($logoPath, 'school_logo', '5.jpg', 'base64', 'image/jpeg');
                        } catch (\Throwable $imgEx) {
                            // Non-blocking if CID image attachment encounters path issue
                        }
                    }

                    $mailer->Body = $htmlBody;
                    $mailer->AltBody = $textAltBody;

                    $mailer->send();
                    $mailSuccess = true;
                    $errorMessage = null;
                    error_log("Admission notification email sent successfully via SMTP port {$attempt['port']} to {$recipientEmail} for ref {$appRef} (Status: {$canonicalStatus})");
                    break;
                } catch (\Throwable $e) {
                    $rawError = $mailer->ErrorInfo ?? $e->getMessage();
                    $errorMessage = preg_replace('/password[=:\s]+[^\s;]+/i', 'password=***', $rawError);
                    error_log("Admission notification email failed via port {$attempt['port']} for ref {$appRef}: " . $errorMessage);
                }
            }
        }

        // 9. Record notification in audit log tables
        try {
            self::ensureTableExists($db);
            $logStmt = $db->prepare("
                INSERT INTO admission_email_notifications (
                    application_id,
                    application_reference,
                    recipient_email,
                    status_transition,
                    email_status,
                    error_message,
                    sent_at
                ) VALUES (
                    :app_id,
                    :app_ref,
                    :email,
                    :status,
                    :email_status,
                    :error_msg,
                    NOW()
                )
            ");
            $logStmt->execute([
                ':app_id'       => $applicationId,
                ':app_ref'      => $appRef,
                ':email'        => $recipientEmail,
                ':status'       => $canonicalStatus,
                ':email_status' => $mailSuccess ? 'sent' : 'failed',
                ':error_msg'    => $errorMessage
            ]);

            // Also record in general email_logs table for administrative search
            $genLog = $db->prepare("
                INSERT INTO email_logs (
                    recipient_email,
                    subject,
                    message,
                    status,
                    sent_at,
                    error_message
                ) VALUES (
                    :email,
                    :subject,
                    :message,
                    :status,
                    NOW(),
                    :error_msg
                )
            ");
            $genLog->execute([
                ':email'     => $recipientEmail,
                ':subject'   => $emailContent['subject'],
                ':message'   => "Status notification: {$canonicalStatus} for ref {$appRef}",
                ':status'    => $mailSuccess ? 'sent' : 'failed',
                ':error_msg' => $errorMessage
            ]);
        } catch (\Throwable $logEx) {
            error_log("Error logging admission email notification: " . $logEx->getMessage());
        }

        return [
            'email_sent' => $mailSuccess,
            'message'    => $mailSuccess 
                ? "Notification email sent successfully to {$recipientEmail}." 
                : "Notification recorded (email delivery status: {$errorMessage})."
        ];
    }

    /**
     * Build status-specific text and subject
     */
    public static function buildContent($status, $data) {
        $parentName = htmlspecialchars($data['parent_name'] ?? 'Parent/Guardian', ENT_QUOTES, 'UTF-8');
        $childName  = htmlspecialchars($data['student_name'] ?? 'Your Child', ENT_QUOTES, 'UTF-8');
        $class      = htmlspecialchars($data['class_to_join'] ?? 'General', ENT_QUOTES, 'UTF-8');
        $appRef     = htmlspecialchars($data['application_reference'] ?? '', ENT_QUOTES, 'UTF-8');
        $date       = htmlspecialchars($data['submitted_date'] ?? date('d M Y'), ENT_QUOTES, 'UTF-8');

        switch ($status) {
            case 'accepted':
                return [
                    'subject'       => 'Admission Application — Successful | St. Lawrence Junior School Kabowa',
                    'heading'       => 'Admission Application — Approved',
                    'heading_color' => '#0B2545',
                    'accent_color'  => '#15803D',
                    'body_paragraphs' => [
                        "Dear {$parentName},",
                        "We are pleased to inform you that the admission application submitted for your child, <strong>{$childName}</strong>, has been successfully approved by St. Lawrence Junior School Kabowa.",
                        "We appreciate the confidence you have placed in our school and look forward to welcoming your child into our school community.",
                        "Your application details are provided below for reference:"
                    ],
                    'closing_paragraphs' => [
                        "Please use the admission status link below to review your application status and any available next steps.",
                        "We look forward to working together in supporting your child's academic growth, character and future development."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child'                 => $childName,
                        'Class'                 => $class,
                        'Date'                  => $date
                    ]
                ];

            case 'waitlist':
                return [
                    'subject'       => 'Admission Application — Waiting List | St. Lawrence Junior School Kabowa',
                    'heading'       => 'Admission Application — Waiting List',
                    'heading_color' => '#0B2545',
                    'accent_color'  => '#B45309',
                    'body_paragraphs' => [
                        "Dear {$parentName},",
                        "Thank you for choosing St. Lawrence Junior School Kabowa and for submitting an admission application for your child, <strong>{$childName}</strong>.",
                        "We wish to inform you that your child's application has been placed on the waiting list.",
                        "This means that the application remains under consideration while available places are being confirmed.",
                        "We sincerely appreciate your patience and understanding. Please retain your application reference and use the status link below to check for future updates."
                    ],
                    'closing_paragraphs' => [
                        "Should a vacancy arise in {$class}, the admissions office will contact you directly."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child'                 => $childName,
                        'Class'                 => $class,
                        'Date'                  => $date
                    ]
                ];

            case 'rejected':
                return [
                    'subject'       => 'Admission Application — Update | St. Lawrence Junior School Kabowa',
                    'heading'       => 'Admission Application — Update',
                    'heading_color' => '#0B2545',
                    'accent_color'  => '#64748B',
                    'body_paragraphs' => [
                        "Dear {$parentName},",
                        "Thank you for considering St. Lawrence Junior School Kabowa and for taking the time to submit an admission application for your child, <strong>{$childName}</strong>.",
                        "Following careful consideration of the application, we regret to inform you that we are unable to offer your child a place at the school at this time.",
                        "We understand that this may be disappointing, and we sincerely appreciate the confidence you placed in our school.",
                        "We wish your child every success in their continued education and future endeavours.",
                        "You may use the status link below to view the current application status."
                    ],
                    'closing_paragraphs' => [],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child'                 => $childName,
                        'Class'                 => $class,
                        'Date'                  => $date
                    ]
                ];

            case 'under_review':
            default:
                return [
                    'subject'       => 'Admission Application — Under Review | St. Lawrence Junior School Kabowa',
                    'heading'       => 'Admission Application — Under Review',
                    'heading_color' => '#0B2545',
                    'accent_color'  => '#0284C7',
                    'body_paragraphs' => [
                        "Dear {$parentName},",
                        "Thank you for submitting an admission application to St. Lawrence Junior School Kabowa for <strong>{$childName}</strong>.",
                        "We wish to let you know that your child's application is currently under review by the school.",
                        "No final admission decision has been made at this stage. We kindly ask for your patience while the application is being considered.",
                        "You may use the status link below to check for updates."
                    ],
                    'closing_paragraphs' => [
                        "Our admissions team will notify you promptly as soon as a determination has been reached."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child'                 => $childName,
                        'Class'                 => $class,
                        'Date'                  => $date
                    ]
                ];
        }
    }

    /**
     * Build the institutional responsive HTML email template
     */
    public static function buildHtmlTemplate($content, $statusCheckUrl) {
        $heading = htmlspecialchars($content['heading'], ENT_QUOTES, 'UTF-8');
        $accentColor = $content['accent_color'] ?? '#0B2545';

        // Render body paragraphs
        $bodyHtml = '';
        foreach ($content['body_paragraphs'] as $p) {
            $bodyHtml .= "<p style=\"margin: 0 0 14px 0; font-size: 15px; line-height: 1.65; color: #334155;\">{$p}</p>";
        }

        // Render non-sensitive details table
        $detailsHtml = '';
        if (!empty($content['details'])) {
            $detailsHtml .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin: 22px 0; border: 1px solid #E2E8F0; border-radius: 4px; background-color: #F8FAFC;">';
            $detailsHtml .= '<tr><td colspan="2" style="background-color: #0B2545; color: #FFFFFF; padding: 10px 16px; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Application Summary</td></tr>';
            foreach ($content['details'] as $lbl => $val) {
                $detailsHtml .= '<tr>';
                $detailsHtml .= '<td style="padding: 10px 16px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 600; color: #475569; width: 40%;">' . htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') . '</td>';
                $detailsHtml .= '<td style="padding: 10px 16px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0B2545;">' . $val . '</td>';
                $detailsHtml .= '</tr>';
            }
            $detailsHtml .= '</table>';
        }

        // Render closing paragraphs
        $closingHtml = '';
        foreach ($content['closing_paragraphs'] as $cp) {
            $closingHtml .= "<p style=\"margin: 14px 0; font-size: 15px; line-height: 1.65; color: #334155;\">{$cp}</p>";
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($content['subject'], ENT_QUOTES, 'UTF-8') . '</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .header-padding { padding: 24px 16px !important; }
            .body-padding { padding: 24px 16px !important; }
            .btn-action { display: block !important; width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F8FAFC; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container Card -->
                <table class="email-container" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                    
                    <!-- Institutional School Header -->
                    <tr>
                        <td class="header-padding" style="background-color: #0B2545; padding: 32px 24px; text-align: center; border-bottom: 4px solid #C9182B;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <!-- Official Logo via CID -->
                                        <img src="cid:school_logo" alt="St. Lawrence Junior School Kabowa Crest" style="width: 82px; height: 82px; display: block; margin: 0 auto 14px auto; border-radius: 50%; background-color: #FFFFFF; padding: 4px;">
                                        <h1 style="color: #FFFFFF; font-size: 20px; font-weight: 700; margin: 0 0 6px 0; letter-spacing: 1px; text-transform: uppercase;">ST. LAWRENCE JUNIOR SCHOOL KABOWA</h1>
                                        <div style="color: #F1F5F9; font-size: 13px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;">WE STRIVE TO EXCEL</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Status Banner / Heading -->
                    <tr>
                        <td style="padding: 24px 32px 0 32px; text-align: left;">
                            <div style="border-left: 4px solid ' . $accentColor . '; padding-left: 12px; margin-bottom: 8px;">
                                <h2 style="color: #0B2545; font-size: 20px; font-weight: 700; margin: 0;">' . $heading . '</h2>
                            </div>
                        </td>
                    </tr>

                    <!-- Email Main Content -->
                    <tr>
                        <td class="body-padding" style="padding: 16px 32px 32px 32px;">
                            ' . $bodyHtml . '
                            ' . $detailsHtml . '
                            ' . $closingHtml . '
                            
                            <!-- Action Button (Check Admission Status) -->
                            <div style="margin: 28px 0 16px 0; text-align: center;">
                                <a href="' . htmlspecialchars($statusCheckUrl, ENT_QUOTES, 'UTF-8') . '" class="btn-action" target="_blank" style="background-color: #0B2545; color: #FFFFFF; text-decoration: none; padding: 14px 32px; font-size: 14px; font-weight: 700; border-radius: 4px; display: inline-block; letter-spacing: 0.5px; border-bottom: 3px solid #C9182B;">
                                    CHECK ADMISSION STATUS
                                </a>
                            </div>
                            <div style="text-align: center; margin-top: 8px;">
                                <span style="font-size: 12px; color: #64748B;">Verify status using your Application Reference and Registered Telephone</span>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Official Institutional Footer -->
                    <tr>
                        <td style="background-color: #0B2545; color: #FFFFFF; padding: 26px 24px; text-align: center; border-top: 1px solid #1E3A8A;">
                            <p style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; letter-spacing: 0.5px; color: #FFFFFF;">
                                St. Lawrence Junior School Kabowa
                            </p>
                            <p style="margin: 0 0 6px 0; font-size: 12px; color: #CBD5E1; line-height: 1.5;">
                                2 Gabunga Road, Kabowa, Kampala, Uganda
                            </p>
                            <p style="margin: 0 0 14px 0; font-size: 12px; color: #CBD5E1;">
                                Tel: +256 772 420 506 &bull; +256 701 420 506
                            </p>
                            <div style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 12px; font-size: 12px; font-weight: 600; letter-spacing: 1.5px; color: #FFFFFF; text-transform: uppercase;">
                                &ldquo;WE STRIVE TO EXCEL&rdquo;
                            </div>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Send email over HTTPS (Port 443) via Google Apps Script Relay
     * Bypasses cloud outbound SMTP port 25/465/587 restrictions on Railway
     */
    public static function sendViaHttpsRelay($to, $subject, $htmlBody, $textBody = '', $fromName = 'St. Lawrence Junior School Kabowa', $replyTo = null, $logoPath = null) {
        $defaultRelayUrl = 'https://script.google.com/macros/s/AKfycbwYDZgyUZdADQ9CG2vE_zFQ10orFyMpK0efsvAyi4lLGADIissVYqRX669bv4uDHiXI/exec';
        $relayUrl = trim((string)env('GOOGLE_SCRIPT_EMAIL_URL', $defaultRelayUrl));
        if (empty($relayUrl)) {
            $relayUrl = $defaultRelayUrl;
        }

        // Prevent sending to RFC 2606 reserved dummy test domains via live Gmail account
        $domain = strtolower((string)substr(strrchr($to, "@"), 1));
        if (in_array($domain, ['example.com', 'example.org', 'example.net', 'test.com', 'invalid'], true)) {
            return [
                'success' => false,
                'error'   => "Reserved test domain (@{$domain}) skipped on live HTTPS relay."
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'error'   => 'PHP cURL extension is not available.'
            ];
        }

        if (!$logoPath) {
            $logoPath = realpath(__DIR__ . '/../../../img/5.jpg');
        }
        $logoBase64 = ($logoPath && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;

        $payload = json_encode([
            'to'         => $to,
            'subject'    => $subject,
            'htmlBody'   => $htmlBody,
            'textBody'   => $textBody ?: strip_tags($htmlBody),
            'fromName'   => $fromName,
            'replyTo'    => $replyTo ?: env('SMTP_FROM_EMAIL', 'stlawrencejuniorschoolkabowa@gmail.com'),
            'logoBase64' => $logoBase64
        ]);

        $ch = curl_init($relayUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlErr)) {
            return [
                'success' => false,
                'error'   => 'HTTPS relay connection error: ' . $curlErr
            ];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded) && !empty($decoded['success'])) {
            return [
                'success' => true,
                'error'   => null
            ];
        }

        $errMsg = is_array($decoded) && !empty($decoded['error'])
            ? $decoded['error']
            : "HTTP {$httpCode}: " . substr(trim(strip_tags((string)$response)), 0, 180);

        return [
            'success' => false,
            'error'   => $errMsg
        ];
    }

    /**
     * Resolve the status check page URL
     */
    private static function getStatusCheckUrl() {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        if ($isLocal) {
            return "http://{$host}/AdvancedPHP/st%20lawrence%20school/frontend/Admission-redesign.html#admission-application-section";
        }
        $publicSite = rtrim((string)env('PUBLIC_SITE_URL', 'https://stlawrencejuniorschoolkabowa.com'), '/');
        return "{$publicSite}/admissions#admission-application-section";
    }
}
