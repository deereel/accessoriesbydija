<?php
// Test mail script for Brevo SMTP configuration
require_once 'includes/email.php';

// Test email details
$test_email = 'biodunoladayo@gmail.com'; // Replace with your actual test email
$subject = 'Test Email from Dija Accessories';
$body = "This is a test email to verify the Brevo SMTP configuration is working.\n\nSent at: " . date('Y-m-d H:i:s');

echo "<h1>Testing Brevo SMTP Mailer</h1>";
echo "<p>Sending test email to: <strong>$test_email</strong></p>";
echo "<p>Subject: <strong>$subject</strong></p>";
echo "<p>Body:</p><pre>$body</pre>";

$error_msg = '';
$result = send_email_smtp($test_email, $subject, $body, false, $error_msg);

if ($result) {
    echo "<p style='color: green;'><strong>✓ Email sent successfully!</strong></p>";
    echo "<p>Check your inbox (and spam folder) for the test email.</p>";
} else {
    echo "<p style='color: red;'><strong>✗ Email failed to send.</strong></p>";
    if ($error_msg) {
        echo "<p><strong>Error:</strong> $error_msg</p>";
    }
    echo "<p><strong>Debug Info:</strong></p>";
    echo "<ul>";
    echo "<li>MAIL_HOST: " . ($_ENV['MAIL_HOST'] ?? 'Not set') . "</li>";
    echo "<li>MAIL_PORT: " . ($_ENV['MAIL_PORT'] ?? 'Not set') . "</li>";
    echo "<li>MAIL_USERNAME: " . ($_ENV['MAIL_USERNAME'] ?? 'Not set') . "</li>";
    echo "<li>MAIL_FROM_ADDRESS: " . ($_ENV['MAIL_FROM_ADDRESS'] ?? 'Not set') . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='test_mail.php'>Refresh to test again</a></p>";
?>