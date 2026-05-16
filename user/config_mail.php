<?php
/**
 * config/mail.php
 *
 * Email helper using PHPMailer.
 * Install PHPMailer via Composer: composer require phpmailer/phpmailer
 *
 * Set your SMTP credentials below.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ── CONFIGURE THESE ────────────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');         // SMTP server
define('MAIL_PORT',     587);                       // 587 for TLS, 465 for SSL
define('MAIL_USERNAME', 'yourmail@gmail.com');      // Your email
define('MAIL_PASSWORD', 'your_app_password');       // App password (not your login)
define('MAIL_FROM',     'yourmail@gmail.com');      // Sender address
define('MAIL_FROM_NAME','The Sanctuary Spa');       // Sender name
define('ADMIN_EMAIL',   'admin@yourspa.com');       // Admin notification target
// ──────────────────────────────────────────────────────────

/**
 * Send an email.
 *
 * @param string $to         Recipient email address
 * @param string $subject    Email subject
 * @param string $body       HTML (or plain-text) body
 * @param bool   $isHtml     true for HTML, false for plain text
 * @throws Exception
 */
function sendEmail(string $to, string $subject, string $body, bool $isHtml = true): void
{
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;

    // Sender & recipient
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($to);
    $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

    // Content
    $mail->isHTML($isHtml);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    if ($isHtml) {
        $mail->AltBody = strip_tags($body);
    }

    $mail->send();
}
