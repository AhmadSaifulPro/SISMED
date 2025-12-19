<?php
/**
 * Mail Configuration
 * PulTech Social Media Application
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Mail settings - configure for your SMTP server
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'ahmadsaifullpro@gmail.com');
define('MAIL_PASSWORD', 'fjxsjqtyrehodtiq');
define('MAIL_FROM_NAME', 'PulTech Social');
define('MAIL_FROM_ADDRESS', 'ahmadsaifullpro@gmail.com');

/**
 * Send email using PHPMailer
 */
function sendMail($to, $subject, $body, $isHtml = true)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;

        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $token, $username)
{
    $resetLink = BASE_URL . '/auth/reset-password.php?token=' . $token;

    $subject = 'Reset Password - ' . APP_NAME;

    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 Reset Password</h1>
            </div>
            <div class="content">
                <p>Halo <strong>' . htmlspecialchars($username) . '</strong>,</p>
                <p>Kami menerima permintaan untuk reset password akun PulTech Social Anda.</p>
                <p>Klik tombol di bawah untuk membuat password baru:</p>
                <p style="text-align: center;">
                    <a href="' . $resetLink . '" class="button">Reset Password</a>
                </p>
                <p>Atau copy link berikut ke browser Anda:</p>
                <p style="word-break: break-all; background: #e5e7eb; padding: 10px; border-radius: 5px; font-size: 12px;">' . $resetLink . '</p>
                <p><strong>Link ini akan kadaluarsa dalam 1 jam.</strong></p>
                <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' PulTech Social. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';

    return sendMail($email, $subject, $body);
}
