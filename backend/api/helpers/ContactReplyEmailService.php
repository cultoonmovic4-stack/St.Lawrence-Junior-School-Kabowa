<?php
/**
 * St. Lawrence Junior School Kabowa
 * Contact Inquiry Reply Email Service
 * 
 * Styled institutionally matching the admission emails:
 * - Official school logo (/img/5.jpg) via CID (cid:school_logo)
 * - Deep Blue (#0B2545) and Crimson (#C9182B) school branding
 * - School Motto: "WE STRIVE TO EXCEL"
 * - Quoted original inquiry box + clear administrative response card
 * - Automatic logging to email_logs table
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContactReplyEmailService {

    /**
     * Send an institutional email reply to an inquirer
     * 
     * @param PDO $db
     * @param array $inquiry [name, email, subject, message, submitted_date]
     * @param string $replyMessage
     * @param string $adminName
     * @return array [success => bool, message => string]
     */
    public static function sendReplyEmail($db, array $inquiry, string $replyMessage, string $adminName = 'School Administration') {
        $recipientEmail = trim($inquiry['email'] ?? '');
        $recipientName  = trim($inquiry['name'] ?? 'Valued Inquirer');
        $originalSubject = trim($inquiry['subject'] ?? 'Website Inquiry');
        $originalMessage = trim($inquiry['message'] ?? '');
        $submittedDate   = !empty($inquiry['submitted_date']) ? date('d M Y, h:i A', strtotime($inquiry['submitted_date'])) : date('d M Y');

        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid recipient email address.'
            ];
        }

        $emailSubject = 'Response to your Inquiry: ' . $originalSubject . ' | St. Lawrence Junior School Kabowa';

        // Build HTML template
        $htmlBody = self::buildHtmlTemplate([
            'recipient_name'   => $recipientName,
            'original_subject' => $originalSubject,
            'original_message' => $originalMessage,
            'submitted_date'   => $submittedDate,
            'reply_message'    => $replyMessage,
            'admin_name'       => $adminName,
            'subject'          => $emailSubject
        ]);

        $textAltBody = strip_tags(str_replace(['<br>', '</p>', '</div>', '</tr>'], "\n", $htmlBody));

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
            $mailer->Port = (int)env('SMTP_PORT', 587);
            $mailer->CharSet = 'UTF-8';

            $fromEmail = env('SMTP_FROM_EMAIL', 'stlawrencejuniorschoolkabowa@gmail.com');
            $fromName  = env('SMTP_FROM_NAME', 'St. Lawrence Junior School Kabowa');
            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addAddress($recipientEmail, $recipientName);
            $mailer->addReplyTo($fromEmail, $fromName);
            $mailer->Subject = $emailSubject;
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
            error_log("Contact reply email sent successfully to {$recipientEmail} for inquiry '{$originalSubject}'");

        } catch (Exception $e) {
            $mailSuccess = false;
            $errorMessage = $mailer->ErrorInfo ?: $e->getMessage();
            error_log("Contact reply email failed for {$recipientEmail}: {$errorMessage}");
        }

        // Log to email_logs
        try {
            if ($db instanceof PDO) {
                $logStmt = $db->prepare("
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
                $logStmt->execute([
                    ':email'     => $recipientEmail,
                    ':subject'   => $emailSubject,
                    ':message'   => "Reply to inquiry: {$originalSubject}\n\n" . substr($replyMessage, 0, 500),
                    ':status'    => $mailSuccess ? 'sent' : 'failed',
                    ':error_msg' => $errorMessage
                ]);
            }
        } catch (Exception $logEx) {
            error_log("Error logging contact reply email: " . $logEx->getMessage());
        }

        return [
            'success'       => $mailSuccess,
            'message'       => $mailSuccess 
                ? "Reply sent successfully to {$recipientEmail}." 
                : "Reply recorded but email could not be sent: {$errorMessage}",
            'error_message' => $errorMessage
        ];
    }

    /**
     * Build institutional HTML email template
     */
    private static function buildHtmlTemplate(array $data): string {
        $recipientName   = htmlspecialchars($data['recipient_name'], ENT_QUOTES, 'UTF-8');
        $originalSubject = htmlspecialchars($data['original_subject'], ENT_QUOTES, 'UTF-8');
        $originalMessage = nl2br(htmlspecialchars($data['original_message'], ENT_QUOTES, 'UTF-8'));
        $submittedDate   = htmlspecialchars($data['submitted_date'], ENT_QUOTES, 'UTF-8');
        $replyContent    = nl2br(htmlspecialchars($data['reply_message'], ENT_QUOTES, 'UTF-8'));
        $adminName       = htmlspecialchars($data['admin_name'], ENT_QUOTES, 'UTF-8');
        $subjectTitle    = htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $subjectTitle . '</title>
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
                <table class="email-container" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    
                    <!-- Institutional School Header -->
                    <tr>
                        <td class="header-padding" style="background-color: #0B2545; padding: 32px 24px; text-align: center; border-bottom: 4px solid #C9182B;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <!-- Official Logo via CID -->
                                        <img src="cid:school_logo" alt="St. Lawrence Junior School Kabowa Crest" style="width: 82px; height: 82px; display: block; margin: 0 auto 14px auto; border-radius: 50%; background-color: #FFFFFF; padding: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                        <h1 style="color: #FFFFFF; font-size: 20px; font-weight: 700; margin: 0 0 6px 0; letter-spacing: 1px; text-transform: uppercase;">ST. LAWRENCE JUNIOR SCHOOL KABOWA</h1>
                                        <div style="color: #F1F5F9; font-size: 13px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;">WE STRIVE TO EXCEL</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Status Banner / Heading -->
                    <tr>
                        <td style="padding: 26px 32px 0 32px; text-align: left;">
                            <div style="border-left: 4px solid #0B2545; padding-left: 12px;">
                                <h2 style="color: #0B2545; font-size: 19px; font-weight: 700; margin: 0;">Official Response to Your Inquiry</h2>
                                <span style="font-size: 12px; color: #64748B; font-weight: 500;">St. Lawrence Administration &amp; Admissions Office</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Email Main Content -->
                    <tr>
                        <td class="body-padding" style="padding: 20px 32px 32px 32px;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.65; color: #334155;">
                                Dear <strong>' . $recipientName . '</strong>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.65; color: #334155;">
                                Thank you for reaching out to <strong>St. Lawrence Junior School Kabowa</strong>. We have reviewed your inquiry regarding <em>"' . $originalSubject . '"</em>, and we are pleased to provide you with our official administrative response below.
                            </p>

                            <!-- Official Institutional Response Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0; border: 2px solid #0B2545; border-radius: 6px; overflow: hidden; background-color: #FFFFFF;">
                                <tr>
                                    <td style="background-color: #0B2545; color: #FFFFFF; padding: 12px 18px; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                                        Our Response
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 22px 20px; background-color: #F8FAFC; font-size: 15px; line-height: 1.7; color: #1E293B;">
                                        ' . $replyContent . '
                                    </td>
                                </tr>
                            </table>

                            <!-- Original Inquiry Summary Table -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 22px 0; border: 1px solid #E2E8F0; border-radius: 6px; overflow: hidden; background-color: #FFFFFF;">
                                <tr>
                                    <td colspan="2" style="background-color: #F1F5F9; color: #475569; padding: 10px 16px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; border-bottom: 1px solid #E2E8F0;">
                                        Summary of Your Submitted Inquiry
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #F1F5F9; font-size: 13px; font-weight: 600; color: #64748B; width: 30%;">Date Received:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #F1F5F9; font-size: 13px; color: #334155;">' . $submittedDate . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #F1F5F9; font-size: 13px; font-weight: 600; color: #64748B;">Subject:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #F1F5F9; font-size: 13px; font-weight: 600; color: #0B2545;">' . $originalSubject . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; font-size: 13px; font-weight: 600; color: #64748B; vertical-align: top;">Your Message:</td>
                                    <td style="padding: 10px 16px; font-size: 13px; color: #475569; font-style: italic; line-height: 1.6;">' . $originalMessage . '</td>
                                </tr>
                            </table>

                            <p style="margin: 20px 0 10px 0; font-size: 14px; line-height: 1.65; color: #475569;">
                                If you require any additional information or have further questions, please do not hesitate to reply directly to this email or reach us via our administrative contacts below.
                            </p>

                            <!-- Sign-off -->
                            <div style="margin-top: 26px; padding-top: 18px; border-top: 1px solid #E2E8F0;">
                                <p style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0B2545;">Warm regards,</p>
                                <p style="margin: 0 0 2px 0; font-size: 14px; font-weight: 600; color: #1E293B;">' . $adminName . '</p>
                                <p style="margin: 0; font-size: 13px; color: #64748B;">St. Lawrence Junior School Kabowa</p>
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
                                P.O.BOX 36198, Kampala, Uganda &bull; 2 Gabunga Road, Kabowa
                            </p>
                            <p style="margin: 0 0 10px 0; font-size: 12px; color: #CBD5E1;">
                                Tel: +256 772 420 506 &bull; +256 701 420 506
                            </p>
                            <div style="height: 1px; background-color: rgba(255,255,255,0.15); margin: 12px auto; max-width: 300px;"></div>
                            <p style="margin: 0; font-size: 11px; color: #94A3B8; letter-spacing: 0.5px;">
                                &copy; ' . date('Y') . ' St. Lawrence Junior School Kabowa. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
