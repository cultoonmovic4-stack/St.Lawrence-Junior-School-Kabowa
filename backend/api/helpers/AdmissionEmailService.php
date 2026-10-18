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
     * Build status-specific text, colors, badges, and subject
     */
    public static function buildContent($status, $data) {
        $parentName  = htmlspecialchars($data['parent_name'] ?? 'Parent/Guardian', ENT_QUOTES, 'UTF-8');
        $childName   = htmlspecialchars($data['student_name'] ?? 'Your Child', ENT_QUOTES, 'UTF-8');
        $class       = htmlspecialchars($data['class_to_join'] ?? 'General', ENT_QUOTES, 'UTF-8');
        $appRef      = htmlspecialchars($data['application_reference'] ?? '', ENT_QUOTES, 'UTF-8');
        $date        = htmlspecialchars($data['submitted_date'] ?? date('d M Y'), ENT_QUOTES, 'UTF-8');
        $reviewNotes = trim((string)($data['review_notes'] ?? ''));

        switch ($status) {
            case 'accepted':
                return [
                    'status_key'         => 'accepted',
                    'subject'            => 'Admission Application — Successful | St. Lawrence Junior School Kabowa',
                    'heading'            => 'Admission Application — Approved',
                    'subheading'         => "Congratulations! {$childName} has been accepted for admission.",
                    'badge_text'         => '&#10003; APPLICATION ACCEPTED',
                    'status_pill_label'  => '&#10003; ACCEPTED &amp; APPROVED',
                    'icon_html'          => '&#10003;',
                    'accent_color'       => '#16A34A',
                    'heading_color'      => '#14532D',
                    'banner_bg'          => '#ECFDF5',
                    'banner_border'      => '#4ADE80',
                    'pill_bg'            => '#DCFCE7',
                    'pill_text'          => '#15803D',
                    'pill_border'        => '#86EFAC',
                    'summary_header_bg'  => '#15803D',
                    'button_bg'          => '#16A34A',
                    'button_border'      => '#14532D',
                    'callout_title'      => 'Next Steps for Admission &amp; Registration',
                    'callout_items'      => [
                        'Visit the School Admissions Office at Kabowa with your Application Reference (<strong>' . $appRef . '</strong>) to pick up your official Admission Letter and Requirements Circular.',
                        'Complete the admission registration formalities to secure your child\'s place in <strong>' . $class . '</strong>.',
                        'Contact our Admissions Desk on <strong>+256 772 420 506</strong> or <strong>+256 701 420 506</strong> if you need any assistance.'
                    ],
                    'review_notes'       => $reviewNotes,
                    'body_paragraphs'    => [
                        "Dear <strong>{$parentName}</strong>,",
                        "We are delighted to inform you that the admission application submitted for your child, <strong>{$childName}</strong>, has been <strong>officially approved and accepted</strong> by St. Lawrence Junior School Kabowa.",
                        "We sincerely appreciate the trust and confidence you have placed in our school, and we look forward to welcoming <strong>{$childName}</strong> into our vibrant academic community."
                    ],
                    'closing_paragraphs' => [
                        "You can view your official application status at any time using the button below.",
                        "We look forward to partnering with you in nurturing your child's academic excellence, discipline, and character."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child\'s Full Name'    => $childName,
                        'Class Admitted To'     => $class,
                        'Date Submitted'        => $date
                    ]
                ];

            case 'waitlist':
                return [
                    'status_key'         => 'waitlist',
                    'subject'            => 'Admission Application — Waiting List | St. Lawrence Junior School Kabowa',
                    'heading'            => 'Admission Application — Placed on Waiting List',
                    'subheading'         => "Your child's application is currently on our priority Waiting List.",
                    'badge_text'         => '&#9203; WAITING LIST STATUS',
                    'status_pill_label'  => '&#9203; ON WAITING LIST',
                    'icon_html'          => '&#9203;',
                    'accent_color'       => '#EA580C',
                    'heading_color'      => '#9A3412',
                    'banner_bg'          => '#FFF7ED',
                    'banner_border'      => '#FB923C',
                    'pill_bg'            => '#FFEDD5',
                    'pill_text'          => '#C2410C',
                    'pill_border'        => '#FDBA74',
                    'summary_header_bg'  => '#C2410C',
                    'button_bg'          => '#EA580C',
                    'button_border'      => '#9A3412',
                    'callout_title'      => 'What Being on the Waiting List Means',
                    'callout_items'      => [
                        'Your application for <strong>' . $childName . '</strong> remains under active priority consideration while class capacity for <strong>' . $class . '</strong> is being finalized.',
                        'No action is required from you right now — our Admissions Office will contact you immediately as soon as a slot becomes available.',
                        'Please keep your Application Reference (<strong>' . $appRef . '</strong>) safe to track real-time updates online.'
                    ],
                    'review_notes'       => $reviewNotes,
                    'body_paragraphs'    => [
                        "Dear <strong>{$parentName}</strong>,",
                        "Thank you for choosing St. Lawrence Junior School Kabowa and for submitting an admission application for your child, <strong>{$childName}</strong>.",
                        "Following our initial review, we wish to inform you that your child's application has been placed on the <strong>Waiting List</strong> for <strong>{$class}</strong> while available places are being confirmed."
                    ],
                    'closing_paragraphs' => [
                        "We sincerely appreciate your patience and keen interest in St. Lawrence Junior School Kabowa. You may check for status updates at any time using the button below."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child\'s Full Name'    => $childName,
                        'Class Applied For'     => $class,
                        'Date Submitted'        => $date
                    ]
                ];

            case 'rejected':
                return [
                    'status_key'         => 'rejected',
                    'subject'            => 'Admission Application — Update | St. Lawrence Junior School Kabowa',
                    'heading'            => 'Admission Application — Not Approved',
                    'subheading'         => "We are unable to offer an admission placement for {$childName} at this time.",
                    'badge_text'         => '&#10005; APPLICATION NOT APPROVED',
                    'status_pill_label'  => '&#10005; NOT APPROVED / DECLINED',
                    'icon_html'          => '&#10005;',
                    'accent_color'       => '#DC2626',
                    'heading_color'      => '#991B1B',
                    'banner_bg'          => '#FEF2F2',
                    'banner_border'      => '#F87171',
                    'pill_bg'            => '#FEE2E2',
                    'pill_text'          => '#B91C1C',
                    'pill_border'        => '#FCA5A5',
                    'summary_header_bg'  => '#B91C1C',
                    'button_bg'          => '#DC2626',
                    'button_border'      => '#991B1B',
                    'callout_title'      => 'Important Notice Regarding Your Application',
                    'callout_items'      => [
                        'Due to high demand and limited class intake capacity for <strong>' . $class . '</strong>, we are unable to offer a placement in this admission cycle.',
                        'This decision does not prevent you from re-applying in a future academic term or year.',
                        'For any inquiries, you may reach our Admissions Office on <strong>+256 772 420 506</strong>.'
                    ],
                    'review_notes'       => $reviewNotes,
                    'body_paragraphs'    => [
                        "Dear <strong>{$parentName}</strong>,",
                        "Thank you for considering St. Lawrence Junior School Kabowa and for taking the time to submit an admission application for your child, <strong>{$childName}</strong>.",
                        "After careful review of all applications received for <strong>{$class}</strong>, we regret to inform you that your child's admission application was <strong>not approved</strong> for placement at this time."
                    ],
                    'closing_paragraphs' => [
                        "We understand this news may be disappointing, and we truly appreciate your interest in our school. We wish <strong>{$childName}</strong> the very best in their educational journey."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child\'s Full Name'    => $childName,
                        'Class Applied For'     => $class,
                        'Date Submitted'        => $date
                    ]
                ];

            case 'under_review':
            default:
                return [
                    'status_key'         => 'under_review',
                    'subject'            => 'Admission Application — Under Review | St. Lawrence Junior School Kabowa',
                    'heading'            => 'Admission Application — Under Review',
                    'subheading'         => "Your child's application is currently being reviewed by our Admissions Committee.",
                    'badge_text'         => '&#128269; APPLICATION UNDER REVIEW',
                    'status_pill_label'  => '&#128269; UNDER REVIEW',
                    'icon_html'          => '&#8505;',
                    'accent_color'       => '#0284C7',
                    'heading_color'      => '#075985',
                    'banner_bg'          => '#F0F9FF',
                    'banner_border'      => '#38BDF8',
                    'pill_bg'            => '#E0F2FE',
                    'pill_text'          => '#0369A1',
                    'pill_border'        => '#7DD3FC',
                    'summary_header_bg'  => '#0369A1',
                    'button_bg'          => '#0284C7',
                    'button_border'      => '#075985',
                    'callout_title'      => 'What Happens Next',
                    'callout_items'      => [
                        'Our Admissions Committee is currently verifying the application details submitted for <strong>' . $childName . '</strong>.',
                        'You will receive an automatic email notification as soon as an official admission decision is made.'
                    ],
                    'review_notes'       => $reviewNotes,
                    'body_paragraphs'    => [
                        "Dear <strong>{$parentName}</strong>,",
                        "Thank you for submitting an admission application to St. Lawrence Junior School Kabowa for <strong>{$childName}</strong>.",
                        "We wish to let you know that your child's application is currently <strong>under review</strong> by the school's Admissions Office."
                    ],
                    'closing_paragraphs' => [
                        "Our admissions team will notify you promptly as soon as a determination has been reached."
                    ],
                    'details' => [
                        'Application Reference' => $appRef,
                        'Child\'s Full Name'    => $childName,
                        'Class Applied For'     => $class,
                        'Date Submitted'        => $date
                    ]
                ];
        }
    }

    /**
     * Build the institutional responsive HTML email template with color-coded status themes
     */
    public static function buildHtmlTemplate($content, $statusCheckUrl) {
        $heading         = htmlspecialchars($content['heading'], ENT_QUOTES, 'UTF-8');
        $subheading      = $content['subheading'] ?? '';
        $badgeText       = $content['badge_text'] ?? 'STATUS UPDATE';
        $statusPillLabel = $content['status_pill_label'] ?? 'UPDATED';
        $iconHtml        = $content['icon_html'] ?? '&#10003;';
        $accentColor     = $content['accent_color'] ?? '#0B2545';
        $headingColor    = $content['heading_color'] ?? '#0B2545';
        $bannerBg        = $content['banner_bg'] ?? '#F8FAFC';
        $bannerBorder    = $content['banner_border'] ?? '#CBD5E1';
        $pillBg          = $content['pill_bg'] ?? '#E2E8F0';
        $pillText        = $content['pill_text'] ?? '#1E293B';
        $pillBorder      = $content['pill_border'] ?? '#CBD5E1';
        $summaryHeaderBg = $content['summary_header_bg'] ?? '#0B2545';
        $buttonBg        = $content['button_bg'] ?? '#0B2545';
        $buttonBorder    = $content['button_border'] ?? '#C9182B';

        // Render body paragraphs
        $bodyHtml = '';
        foreach ($content['body_paragraphs'] as $p) {
            $bodyHtml .= "<p style=\"margin: 0 0 15px 0; font-size: 15px; line-height: 1.7; color: #334155;\">{$p}</p>";
        }

        // Render non-sensitive details table with prominent colored status row
        $detailsHtml = '';
        if (!empty($content['details'])) {
            $detailsHtml .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0; border: 2px solid ' . $bannerBorder . '; border-radius: 8px; overflow: hidden; background-color: #FFFFFF;">';
            $detailsHtml .= '<tr><td colspan="2" style="background-color: ' . $summaryHeaderBg . '; color: #FFFFFF; padding: 12px 18px; font-size: 13px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase;">Application Summary</td></tr>';

            // Prominent Decision Status Row first
            $detailsHtml .= '<tr>';
            $detailsHtml .= '<td style="padding: 12px 18px; border-bottom: 1px solid #E2E8F0; background-color: ' . $bannerBg . '; font-size: 13px; font-weight: 700; color: #334155; width: 42%;">Decision Status</td>';
            $detailsHtml .= '<td style="padding: 12px 18px; border-bottom: 1px solid #E2E8F0; background-color: ' . $bannerBg . ';">';
            $detailsHtml .= '<span style="display: inline-block; background-color: ' . $pillBg . '; color: ' . $pillText . '; border: 1px solid ' . $pillBorder . '; padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase;">' . $statusPillLabel . '</span>';
            $detailsHtml .= '</td>';
            $detailsHtml .= '</tr>';

            foreach ($content['details'] as $lbl => $val) {
                $detailsHtml .= '<tr>';
                $detailsHtml .= '<td style="padding: 11px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 600; color: #475569; width: 42%; background-color: #F8FAFC;">' . htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') . '</td>';
                $detailsHtml .= '<td style="padding: 11px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0B2545; background-color: #FFFFFF;">' . $val . '</td>';
                $detailsHtml .= '</tr>';
            }
            $detailsHtml .= '</table>';
        }

        // Optional Administrative Review Notes box if provided by admin
        $notesHtml = '';
        if (!empty($content['review_notes'])) {
            $safeNotes = nl2br(htmlspecialchars($content['review_notes'], ENT_QUOTES, 'UTF-8'));
            $notesHtml .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 22px 0; background-color: #F8FAFC; border-left: 4px solid ' . $accentColor . '; border-radius: 4px;">';
            $notesHtml .= '<tr><td style="padding: 14px 18px;">';
            $notesHtml .= '<div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: ' . $headingColor . '; margin-bottom: 6px;">Admissions Office Note</div>';
            $notesHtml .= '<div style="font-size: 14px; line-height: 1.6; color: #334155;">' . $safeNotes . '</div>';
            $notesHtml .= '</td></tr></table>';
        }

        // Render themed Callout / Next Steps box
        $calloutHtml = '';
        if (!empty($content['callout_items'])) {
            $calloutTitle = $content['callout_title'] ?? 'Important Information';
            $calloutHtml .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin: 22px 0; background-color: ' . $bannerBg . '; border: 1px solid ' . $bannerBorder . '; border-radius: 8px;">';
            $calloutHtml .= '<tr><td style="padding: 18px 20px;">';
            $calloutHtml .= '<div style="font-size: 14px; font-weight: 800; color: ' . $headingColor . '; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">' . $calloutTitle . '</div>';
            $calloutHtml .= '<table width="100%" cellpadding="0" cellspacing="0">';
            foreach ($content['callout_items'] as $item) {
                $calloutHtml .= '<tr>';
                $calloutHtml .= '<td valign="top" width="22" style="padding: 4px 0; color: ' . $accentColor . '; font-size: 15px; font-weight: 900;">&#8226;</td>';
                $calloutHtml .= '<td valign="top" style="padding: 4px 0; font-size: 14px; line-height: 1.6; color: #1E293B;">' . $item . '</td>';
                $calloutHtml .= '</tr>';
            }
            $calloutHtml .= '</table>';
            $calloutHtml .= '</td></tr></table>';
        }

        // Render closing paragraphs
        $closingHtml = '';
        foreach ($content['closing_paragraphs'] as $cp) {
            $closingHtml .= "<p style=\"margin: 14px 0; font-size: 15px; line-height: 1.7; color: #334155;\">{$cp}</p>";
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
            .btn-action { display: block !important; width: 100% !important; text-align: center !important; box-sizing: border-box !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F1F5F9; padding: 32px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container Card -->
                <table class="email-container" width="620" cellpadding="0" cellspacing="0" style="max-width: 620px; width: 100%; background-color: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 24px rgba(11, 37, 69, 0.08);">
                    
                    <!-- Institutional School Header -->
                    <tr>
                        <td class="header-padding" style="background-color: #0B2545; padding: 30px 24px; text-align: center; border-bottom: 4px solid #C9182B;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <!-- Official Logo via CID -->
                                        <img src="cid:school_logo" alt="St. Lawrence Junior School Kabowa Crest" style="width: 84px; height: 84px; display: block; margin: 0 auto 14px auto; border-radius: 50%; background-color: #FFFFFF; padding: 5px; box-shadow: 0 4px 10px rgba(0,0,0,0.25);">
                                        <h1 style="color: #FFFFFF; font-size: 21px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: 1px; text-transform: uppercase;">ST. LAWRENCE JUNIOR SCHOOL KABOWA</h1>
                                        <div style="color: #F1F5F9; font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;">WE STRIVE TO EXCEL</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Color-Coded Status Hero Banner (Green = Accepted, Orange = Waitlist, Red = Rejected) -->
                    <tr>
                        <td style="background-color: ' . $bannerBg . '; border-bottom: 3px solid ' . $accentColor . '; padding: 26px 32px; text-align: center;">
                            <!-- Prominent Status Pill Badge -->
                            <div style="margin-bottom: 12px;">
                                <span style="display: inline-block; background-color: ' . $accentColor . '; color: #FFFFFF; font-size: 13px; font-weight: 800; letter-spacing: 1px; padding: 8px 20px; border-radius: 999px; text-transform: uppercase; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                                    ' . $badgeText . '
                                </span>
                            </div>
                            <!-- Status Title -->
                            <h2 style="color: ' . $headingColor . '; font-size: 22px; font-weight: 800; margin: 0 0 6px 0; line-height: 1.3;">
                                ' . $heading . '
                            </h2>
                            <!-- Status Subtitle -->
                            <p style="color: ' . $pillText . '; font-size: 15px; font-weight: 600; margin: 0; line-height: 1.5;">
                                ' . $subheading . '
                            </p>
                        </td>
                    </tr>

                    <!-- Email Main Content -->
                    <tr>
                        <td class="body-padding" style="padding: 28px 32px 34px 32px;">
                            ' . $bodyHtml . '
                            ' . $detailsHtml . '
                            ' . $notesHtml . '
                            ' . $calloutHtml . '
                            ' . $closingHtml . '
                            
                            <!-- Action Button (Check Admission Status) -->
                            <div style="margin: 30px 0 14px 0; text-align: center;">
                                <a href="' . htmlspecialchars($statusCheckUrl, ENT_QUOTES, 'UTF-8') . '" class="btn-action" target="_blank" style="background-color: ' . $buttonBg . '; color: #FFFFFF; text-decoration: none; padding: 15px 34px; font-size: 14px; font-weight: 800; border-radius: 6px; display: inline-block; letter-spacing: 0.8px; border-bottom: 4px solid ' . $buttonBorder . '; box-shadow: 0 4px 10px rgba(0,0,0,0.12);">
                                    CHECK ADMISSION STATUS
                                </a>
                            </div>
                            <div style="text-align: center; margin-top: 8px;">
                                <span style="font-size: 12px; color: #64748B;">Verify status online using your Application Reference and Registered Telephone</span>
                            </div>

                            <!-- Official Sign-Off -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 28px; border-top: 1px solid #E2E8F0; padding-top: 18px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0B2545;">Warm regards,</p>
                                        <p style="margin: 0 0 2px 0; font-size: 14px; font-weight: 700; color: ' . $accentColor . ';">Admissions &amp; Administration Office</p>
                                        <p style="margin: 0; font-size: 13px; color: #64748B;">St. Lawrence Junior School Kabowa</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Official Institutional Footer -->
                    <tr>
                        <td style="background-color: #0B2545; color: #FFFFFF; padding: 26px 24px; text-align: center; border-top: 4px solid ' . $accentColor . ';">
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
