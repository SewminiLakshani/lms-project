<?php
// =====================================================
// Email Notification Helper
// Uses PHP's built-in mail() function.
//
// IMPORTANT (XAMPP/WAMP users):
// mail() does NOT work out-of-the-box on localhost because there's
// no mail server configured. To actually receive emails while testing:
//
//   OPTION A (easiest for coursework demos):
//     Install "Mercury Mail" (bundled with older XAMPP) or use a tool
//     like "sendmail for Windows" / smtp4dev to catch local emails.
//
//   OPTION B (recommended for a real working demo):
//     Replace the body of sendNotificationEmail() below with PHPMailer
//     using Gmail SMTP. Steps:
//       1. composer require phpmailer/phpmailer   (or download manually)
//       2. require the autoloader here
//       3. Use SMTP with an "App Password" from your Gmail account
//     Example PHPMailer code is commented at the bottom of this file.
//
// For your HNDIT report/viva: you can demonstrate this by showing the
// function is called at the right points (grading, new assignment,
// announcements) — even a config note about SMTP setup is fine to
// discuss, examiners mainly want to see the logic and integration.
// =====================================================

function sendNotificationEmail($to, $subject, $message) {
    $headers = "From: EduLMS <no-reply@edulms.local>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $body = "
        <div style='font-family: Arial, sans-serif; max-width:600px; margin:0 auto;'>
          <div style='background:#4338ca; padding:16px; border-radius:8px 8px 0 0;'>
            <h2 style='color:#fff; margin:0;'>EduLMS</h2>
          </div>
          <div style='padding:20px; border:1px solid #e5e7eb; border-top:none; border-radius:0 0 8px 8px;'>
            $message
            <p style='color:#6b7280; font-size:12px; margin-top:24px;'>
              This is an automated notification from EduLMS. Please do not reply to this email.
            </p>
          </div>
        </div>
    ";

    // Suppress warnings if no local mail server is configured (common on XAMPP/WAMP)
    return @mail($to, $subject, $body, $headers);
}

/* =====================================================
   OPTIONAL: PHPMailer + Gmail SMTP version (more reliable)
   Uncomment and configure if you install PHPMailer via Composer:

require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendNotificationEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'youraddress@gmail.com';
        $mail->Password   = 'your-16-char-app-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('youraddress@gmail.com', 'EduLMS');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
===================================================== */
?>
