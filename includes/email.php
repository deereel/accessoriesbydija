<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Load environment variables
require_once __DIR__ . '/../config/env.php';

// Include PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email helper using PHPMailer with SMTP
function send_email_smtp($to, $subject, $body, $is_html = false, &$error_message = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
        $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $_ENV['MAIL_PORT'] ?? 465;

        // Log configuration for debugging
        error_log("Email config - Host: " . $mail->Host . ", Port: " . $mail->Port . ", Username set: " . (!empty($mail->Username) ? 'yes' : 'no') . ", Password set: " . (!empty($mail->Password) ? 'yes' : 'no'));

        // Recipients
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'orders@accessoriesbydija.uk', $_ENV['MAIL_FROM_NAME'] ?? 'Dija Accessories');
        $mail->addAddress($to);

        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($is_html) {
            $mail->AltBody = strip_tags($body);
        }

        error_log("Attempting to send email to $to with subject: $subject");
        $mail->send();
        error_log("Email sent successfully to $to");
        return true;
    } catch (Exception $e) {
        $error_message = $mail->ErrorInfo;
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

function send_order_confirmation_email($pdo, $order_id) {
    // Fetch order and customer details
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $to = $order['customer_email'] ?? null;
    if (!$to) return false; // no email to send to

    // Fetch items
    $it = $pdo->prepare("SELECT oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $it->execute([$order_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);

    $subject = "Order Confirmation - Order #" . $order_id;

    $body = "Hello " . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ",\n\n";
    $body .= "Thank you for your order. Your order number is #" . $order_id . ".\n\n";
    $body .= "Order details:\n";
    foreach ($items as $row) {
        $body .= sprintf(" - %s x %d @ £%.2f = £%.2f\n", $row['name'], $row['quantity'], $row['unit_price'], $row['quantity'] * $row['unit_price']);
    }
    // Calculate subtotal from total + discount - shipping
    $subtotal = ($order['total_amount'] ?? 0) + ($order['discount_amount'] ?? 0) - ($order['shipping_amount'] ?? 0);

    $body .= "\nSubtotal: £" . number_format($subtotal, 2) . "\n";
    $body .= "Shipping: £" . number_format($order['shipping_amount'] ?? 0, 2) . "\n";
    $body .= "Discount: -£" . number_format($order['discount_amount'] ?? 0, 2) . "\n";
    $body .= "Total: £" . number_format($order['total_amount'] ?? 0, 2) . "\n\n";
    $body .= "We will send another email when your order ships.\n\n";
    $body .= "Regards,\nThe Team\n";

    // Use PHPMailer SMTP
    $sent = send_email_smtp($to, $subject, $body);

    // Log to DB (simple analytics / record)
    try {
        $logStmt = $pdo->prepare('INSERT INTO email_logs (order_id, to_email, subject, sent, created_at) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->execute([$order_id, $to, $subject, $sent ? 1 : 0]);
    } catch (Exception $e) {
        // ignore logging errors
    }

    return $sent;
}

function send_generic_email($to, $subject, $body, $headers = []) {
    // Note: headers parameter is ignored in SMTP version, as PHPMailer handles headers internally
    return send_email_smtp($to, $subject, $body);
}

function send_welcome_email($customer_email, $customer_name) {
    $subject = "Welcome to Dija Accessories!";

    $body = "Dear " . htmlspecialchars($customer_name) . ",\n\n";
    $body .= "Welcome to Dija Accessories! Thank you for creating an account with us.\n\n";
    $body .= "Here's what you can do with your new account:\n";
    $body .= "• Save your favorite items to your wishlist\n";
    $body .= "• Save multiple shipping addresses\n";
    $body .= "• Track your order history\n";
    $body .= "• Enjoy faster checkout\n";
    $body .= "• Receive exclusive offers and updates\n\n";
    $body .= "Start shopping now at https://accessoriesbydija.uk\n\n";
    $body .= "If you have any questions, feel free to contact our support team.\n\n";
    $body .= "Best regards,\n";
    $body .= "The Dija Accessories Team\n";
    $body .= "support@accessoriesbydija.uk";

    return send_email_smtp($customer_email, $subject, $body);
}
?>