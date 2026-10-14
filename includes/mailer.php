<?php
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
?>
