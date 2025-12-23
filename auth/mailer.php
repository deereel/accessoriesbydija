<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If using Composer:
require __DIR__ . '/../vendor/autoload.php';

// If manually included, adjust the path:
// require __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
// require __DIR__ . '/../vendor/phpmailer/src/Exception.php';
// require __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'yourname@gmail.com'; // Your Gmail address
        $mail->Password   = 'your_app_password';  // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('yourname@gmail.com', 'Your Site Name');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>