<?php
require_once __DIR__ . '/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Disable debug output
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Debugoutput = function($str, $level) {
            // Suppress all debug output
        };
        
        // Server settings from environment variables
        $port = (int)env('SMTP_PORT', 465);
        $this->mailer->isSMTP();
        $this->mailer->Host = env('SMTP_HOST', 'smtp.gmail.com');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = env('SMTP_USERNAME', 'stlawrencejuniorschoolkabowa@gmail.com');
        $this->mailer->Password = env('SMTP_PASSWORD', 'rlexmpxeyykmgzbv');
        $this->mailer->SMTPSecure = ($port === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $port;
        $this->mailer->Timeout = 6;
        
        // Default sender from environment variables
        $this->mailer->setFrom(
            env('SMTP_FROM_EMAIL', 'stlawrencejuniorschoolkabowa@gmail.com'), 
            env('SMTP_FROM_NAME', 'St. Lawrence Junior School Kabowa')
        );
    }
    
    public function sendReply($to, $toName, $subject, $message) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($to, $toName);
            $this->mailer->Subject = 'Re: ' . $subject;
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            // Attach logo
            $logoPath = __DIR__ . '/../../../img/5.jpg';
            if (file_exists($logoPath)) {
                $this->mailer->addEmbeddedImage($logoPath, 'school_logo', 'school_logo.jpg', 'base64', 'image/jpeg');
                $logoSrc = 'cid:school_logo';
            } else {
                $logoSrc = 'https://via.placeholder.com/100x100/0066cc/ffffff?text=SLJS';
            }
            
            $htmlMessage = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Georgia, serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="650" cellpadding="0" cellspacing="0" style="background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0066cc; padding: 50px 40px; text-align: center; border-bottom: 4px solid #dc3545;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <img src="' . $logoSrc . '" alt="School Logo" style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid white; background: white; padding: 8px; margin-bottom: 20px;">
                                        <h1 style="color: white; font-size: 26px; margin: 0; font-weight: normal; letter-spacing: 2px;">ST. LAWRENCE JUNIOR SCHOOL</h1>
                                        <p style="color: white; font-size: 14px; margin: 8px 0 0 0; letter-spacing: 1px;">KABOWA</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 50px 45px;">
                            
                            <!-- Greeting -->
                            <p style="font-size: 18px; color: #333; margin: 0 0 30px 0; font-family: Georgia, serif;">Dear ' . htmlspecialchars($toName) . ',</p>
                            
                            <!-- Introduction -->
                            <p style="color: #555; line-height: 1.8; font-size: 16px; margin: 0 0 30px 0; font-family: Georgia, serif;">
                                Thank you for contacting St. Lawrence Junior School - Kabowa. We are pleased to respond to your inquiry.
                            </p>
                            
                            <!-- Reply Message Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 35px 0;">
                                <tr>
                                    <td style="border: 2px solid #0066cc; padding: 0;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color: #0066cc; padding: 12px 20px;">
                                                    <p style="color: white; font-size: 14px; margin: 0; font-weight: bold; letter-spacing: 1px;">OUR RESPONSE</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="background-color: #f9f9f9; padding: 25px 20px;">
                                                    <p style="color: #333; line-height: 1.8; font-size: 15px; margin: 0; font-family: Arial, sans-serif;">
                                                        ' . nl2br(htmlspecialchars($message)) . '
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Closing -->
                            <p style="color: #555; line-height: 1.8; font-size: 16px; margin: 30px 0 0 0; font-family: Georgia, serif;">
                                Should you have any further questions, please do not hesitate to contact us. We would be delighted to assist you.
                            </p>
                            
                            <!-- Signature -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 40px;">
                                <tr>
                                    <td style="border-top: 2px solid #e0e0e0; padding-top: 25px;">
                                        <p style="margin: 0 0 5px 0; color: #0066cc; font-size: 16px; font-weight: bold;">Warm regards,</p>
                                        <p style="margin: 0 0 3px 0; color: #333; font-size: 15px; font-weight: bold;">St. Lawrence Junior School</p>
                                        <p style="margin: 0; color: #777; font-size: 14px; font-style: italic;">Admissions & Communications Office</p>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2c3e50; padding: 40px 45px; color: white;">
                            
                            <!-- Contact Title -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td align="center">
                                        <h2 style="color: white; font-size: 20px; margin: 0; font-weight: normal; letter-spacing: 1px;">CONTACT INFORMATION</h2>
                                        <div style="width: 60px; height: 2px; background-color: #dc3545; margin: 10px auto 0 auto;"></div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Contact Grid -->
                            <table width="100%" cellpadding="15" cellspacing="0">
                                <tr>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Phone</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">+256 772 420 506</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">+256 701 420 506</p>
                                    </td>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Email</p>
                                        <p style="color: white; font-size: 14px; margin: 0; line-height: 1.6; word-break: break-word;">stlawrencejuniorschoolkabowa@gmail.com</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Location</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">Kabowa, Kampala</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">Uganda</p>
                                    </td>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Office Hours</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">Monday - Friday</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">8:00 AM - 5:00 PM</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Motto -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.2);">
                                <tr>
                                    <td align="center">
                                        <p style="color: #95a5a6; font-size: 14px; margin: 0; font-style: italic;">"We Strive To Excel"</p>
                                        <p style="color: #7f8c8d; font-size: 12px; margin: 10px 0 0 0;">(c) 2026 St. Lawrence Junior School. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
            
            require_once __DIR__ . '/../helpers/AdmissionEmailService.php';
            $relay = AdmissionEmailService::sendViaHttpsRelay($to, 'Re: ' . $subject, $htmlMessage, strip_tags($message));
            if ($relay['success']) {
                return true;
            }

            $this->mailer->Body = $htmlMessage;
            $this->mailer->AltBody = strip_tags($message);
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    public function sendEmail($to, $subject, $message) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email address: " . $to);
                return false;
            }
            
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            // Attach logo
            $logoPath = __DIR__ . '/../../../img/5.jpg';
            if (file_exists($logoPath)) {
                $this->mailer->addEmbeddedImage($logoPath, 'school_logo', 'school_logo.jpg', 'base64', 'image/jpeg');
                $logoSrc = 'cid:school_logo';
            } else {
                $logoSrc = 'https://via.placeholder.com/100x100/0066cc/ffffff?text=SLJS';
            }
            
            $htmlMessage = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Georgia, serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="650" cellpadding="0" cellspacing="0" style="background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0066cc; padding: 50px 40px; text-align: center; border-bottom: 4px solid #dc3545;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <img src="' . $logoSrc . '" alt="School Logo" style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid white; background: white; padding: 8px; margin-bottom: 20px;">
                                        <h1 style="color: white; font-size: 26px; margin: 0; font-weight: normal; letter-spacing: 2px;">ST. LAWRENCE JUNIOR SCHOOL</h1>
                                        <p style="color: white; font-size: 14px; margin: 8px 0 0 0; letter-spacing: 1px;">KABOWA</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 50px 45px;">
                            <div style="color: #333; line-height: 1.8; font-size: 16px; font-family: Georgia, serif;">
                                ' . $message . '
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2c3e50; padding: 40px 45px; color: white;">
                            
                            <!-- Contact Title -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td align="center">
                                        <h2 style="color: white; font-size: 20px; margin: 0; font-weight: normal; letter-spacing: 1px;">CONTACT INFORMATION</h2>
                                        <div style="width: 60px; height: 2px; background-color: #dc3545; margin: 10px auto 0 auto;"></div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Contact Grid -->
                            <table width="100%" cellpadding="15" cellspacing="0">
                                <tr>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Phone</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">+256 772 420 506</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">+256 701 420 506</p>
                                    </td>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Email</p>
                                        <p style="color: white; font-size: 14px; margin: 0; line-height: 1.6; word-break: break-word;">stlawrencejuniorschoolkabowa@gmail.com</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Location</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">Kabowa, Kampala</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">Uganda</p>
                                    </td>
                                    <td width="50%" valign="top" style="padding: 15px 10px;">
                                        <p style="color: #95a5a6; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">Office Hours</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">Monday - Friday</p>
                                        <p style="color: white; font-size: 15px; margin: 0; line-height: 1.6;">8:00 AM - 5:00 PM</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Motto -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.2);">
                                <tr>
                                    <td align="center">
                                        <p style="color: #95a5a6; font-size: 14px; margin: 0; font-style: italic;">"We Strive To Excel"</p>
                                        <p style="color: #7f8c8d; font-size: 12px; margin: 10px 0 0 0;">(c) 2026 St. Lawrence Junior School. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
            
            require_once __DIR__ . '/../helpers/AdmissionEmailService.php';
            $relay = AdmissionEmailService::sendViaHttpsRelay($to, $subject, $htmlMessage, strip_tags($message));
            if ($relay['success']) {
                return true;
            }

            $this->mailer->Body = $htmlMessage;
            $this->mailer->AltBody = strip_tags($message);
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
