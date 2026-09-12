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

        // 2. Prevent notification if status did not genuinely change
        if ($previousStatus !== null && strtolower(trim($previousStatus)) === $canonicalStatus) {
            return [
                'email_sent' => false,
                'message' => "Status did not change ('{$canonicalStatus}' -> '{$canonicalStatus}'). Notification skipped."
            ];
        }

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

        $recipientEmail = trim($app['responsible_person_email'] ?? '');
        $appRef         = $app['application_reference'] ?? ('SLJK-' . $app['id']);
        $studentName    = trim(($app['student_surname'] ?? '') . ' ' . ($app['student_other_names'] ?? ''));
        $classToJoin    = $app['class_to_join'] ?? 'General';
        $submissionDate = !empty($app['submitted_date']) ? date('d M Y', strtotime($app['submitted_date'])) : date('d M Y');
        $parentName     = $app['responsible_person_name'] ?? 'Parent / Guardian';

        // 4. Validate recipient email
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Admission Email Skipped: No valid email for application {$appRef}");
            return [
                'email_sent' => false,
                'message' => "No valid parent email recorded for this application."
            ];
        }

        // 5. Deduplication check: Has this exact status notification already been successfully sent?
        $dedupStmt = $db->prepare("
            SELECT id, sent_at 
            FROM admission_email_notifications 
            WHERE application_id = :app_id 
              AND status_transition = :status 
              AND email_status = 'sent' 
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
                'message' => "Duplicate notification prevented: {$canonicalStatus} email was already sent previously."
            ];
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

        // 8. Send email using existing PHPMailer infrastructure
        $mailSuccess = false;
        $errorMessage = null;

        try {
            $mailer = new PHPMailer(true);
            $mailer->SMTPDebug = 0;
            $mailer->isSMTP();
            $mailer->Host = env('SMTP_HOST', 'smtp.gmail.com');
            $mailer->SMTPAuth = true;
            $mailer->Username = env('SMTP_USERNAME');
            $mailer->Password = env('SMTP_PASSWORD');
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = env('SMTP_PORT', 587);
            $mailer->CharSet = 'UTF-8';

            $fromEmail = env('SMTP_FROM_EMAIL', 'stlawrencejuniorschoolkabowa@gmail.com');
            $fromName  = env('SMTP_FROM_NAME', 'St. Lawrence Junior School Kabowa');
            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addAddress($recipientEmail, $parentName);
            $mailer->Subject = $emailContent['subject'];
            $mailer->isHTML(true);

            // Embed official school logo using CID
            $logoPath = realpath(__DIR__ . '/../../../img/5.jpg');
            if ($logoPath && file_exists($logoPath)) {
                $mailer->addEmbeddedImage($logoPath, 'school_logo', '5.jpg', 'base64', 'image/jpeg');
            }

            $mailer->Body = $htmlBody;
            $mailer->AltBody = $textAltBody;

            $mailer->send();
            $mailSuccess = true;
            error_log("Admission notification email sent successfully to {$recipientEmail} for ref {$appRef} (Status: {$canonicalStatus})");

        } catch (Exception $e) {
            $mailSuccess = false;
            $rawError = $mailer->ErrorInfo ?? $e->getMessage();
            // Sanitize error to avoid leaking credentials
            $errorMessage = preg_replace('/password[=:\s]+[^\s;]+/i', 'password=***', $rawError);
            error_log("Admission notification email failed for ref {$appRef} to {$recipientEmail}: " . $errorMessage);
        }

        // 9. Record notification in audit log tables
        try {
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
        } catch (Exception $logEx) {
            error_log("Error logging admission email notification: " . $logEx->getMessage());
        }

        return [
            'email_sent' => $mailSuccess,
            'message'    => $mailSuccess 
                ? "Notification email sent to {$recipientEmail}." 
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
     * Resolve the status check page URL
     */
    private static function getStatusCheckUrl() {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return "{$scheme}://{$host}/AdvancedPHP/st%20lawrence%20school/frontend/Admission-redesign.html#admission-application-section";
    }
}
