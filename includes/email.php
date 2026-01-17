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
        $port = $_ENV['MAIL_PORT'] ?? 587;
        $mail->Port = $port;
        $mail->SMTPSecure = $port == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

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

function send_admin_order_notification($pdo, $order_id) {
    // Fetch order details
    $stmt = $pdo->prepare("SELECT o.*, c.first_name, c.last_name, c.email AS customer_email
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    // Fetch order items
    $it = $pdo->prepare("SELECT oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $it->execute([$order_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);

    $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@accessoriesbydija.uk';
    $subject = "New Order Received - Order #" . $order_id;

    $body = "A new order has been placed.\n\n";
    $body .= "Order Details:\n";
    $body .= "Order ID: " . $order_id . "\n";
    $body .= "Order Number: " . $order['order_number'] . "\n";
    $body .= "Customer: " . ($order['first_name'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Guest') . "\n";
    $body .= "Email: " . $order['email'] . "\n";
    $body .= "Total: £" . number_format($order['total_amount'], 2) . "\n\n";

    $body .= "Items:\n";
    foreach ($items as $item) {
        $body .= "- " . $item['name'] . " x" . $item['quantity'] . " @ £" . number_format($item['unit_price'], 2) . "\n";
    }

    $body .= "\nPlease process this order promptly.\n";

    return send_email_smtp($admin_email, $subject, $body);
}

function send_shipping_notification_email($pdo, $order_id, $tracking_number = null, $carrier = null) {
    // Fetch order and customer details
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $to = $order['customer_email'] ?? null;
    if (!$to) return false; // no email to send to

    $subject = "Your Order Has Shipped - Order #" . $order_id;

    $body = "Hello " . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ",\n\n";
    $body .= "Great news! Your order #" . $order_id . " has been shipped and is on its way to you.\n\n";

    if ($tracking_number && $carrier) {
        $body .= "Tracking Information:\n";
        $body .= "Carrier: " . htmlspecialchars($carrier) . "\n";
        $body .= "Tracking Number: " . htmlspecialchars($tracking_number) . "\n\n";
        $body .= "You can track your package at the carrier's website using the tracking number above.\n\n";
    }

    $body .= "Expected Delivery: 3-5 business days from shipping date\n\n";
    $body .= "If you have any questions about your order, please don't hesitate to contact us.\n\n";
    $body .= "Thank you for shopping with Dija Accessories!\n\n";
    $body .= "Regards,\nThe Dija Accessories Team\n";
    $body .= "support@accessoriesbydija.uk";

    // Use PHPMailer SMTP
    $sent = send_email_smtp($to, $subject, $body);

    // Log to DB
    try {
        $logStmt = $pdo->prepare('INSERT INTO email_logs (order_id, to_email, subject, sent, created_at) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->execute([$order_id, $to, $subject, $sent ? 1 : 0]);
    } catch (Exception $e) {
        // ignore logging errors
    }

    return $sent;
}

function send_delivery_confirmation_email($pdo, $order_id) {
    // Fetch order and customer details
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $to = $order['customer_email'] ?? null;
    if (!$to) return false; // no email to send to

    $subject = "Your Order Has Been Delivered - Order #" . $order_id;

    $body = "Hello " . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ",\n\n";
    $body .= "Your order #" . $order_id . " has been successfully delivered!\n\n";
    $body .= "We hope you love your new jewelry pieces. If you have any questions or need assistance, please don't hesitate to contact us.\n\n";
    $body .= "Thank you for choosing Dija Accessories for your jewelry needs.\n\n";
    $body .= "We'd love to hear from you! Please consider leaving a review of your purchase.\n\n";
    $body .= "Regards,\nThe Dija Accessories Team\n";
    $body .= "support@accessoriesbydija.uk";

    // Use PHPMailer SMTP
    $sent = send_email_smtp($to, $subject, $body);

    // Log to DB
    try {
        $logStmt = $pdo->prepare('INSERT INTO email_logs (order_id, to_email, subject, sent, created_at) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->execute([$order_id, $to, $subject, $sent ? 1 : 0]);
    } catch (Exception $e) {
        // ignore logging errors
    }

    return $sent;
}

function send_cancelled_order_email($pdo, $order_id) {
    // Fetch order and customer details
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $to = $order['customer_email'] ?? null;
    if (!$to) return false; // no email to send to

    $subject = "Order Cancelled - Order #" . $order_id;

    $body = "Hello " . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ",\n\n";
    $body .= "We're writing to inform you that your order #" . $order_id . " has been cancelled.\n\n";
    $body .= "If this cancellation was unexpected or if you have any questions, please don't hesitate to contact our support team.\n\n";
    $body .= "If you would like to place a new order, you can do so at any time on our website.\n\n";
    $body .= "We apologize for any inconvenience this may have caused.\n\n";
    $body .= "Regards,\nThe Dija Accessories Team\n";
    $body .= "support@accessoriesbydija.uk";

    // Use PHPMailer SMTP
    $sent = send_email_smtp($to, $subject, $body);

    // Log to DB
    try {
        $logStmt = $pdo->prepare('INSERT INTO email_logs (order_id, to_email, subject, sent, created_at) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->execute([$order_id, $to, $subject, $sent ? 1 : 0]);
    } catch (Exception $e) {
        // ignore logging errors
    }

    return $sent;
}

function send_failed_payment_email($pdo, $order_id) {
    // Fetch order and customer details
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $to = $order['customer_email'] ?? null;
    if (!$to) return false; // no email to send to

    $subject = "Payment Failed - Order #" . $order_id;

    $body = "Hello " . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ",\n\n";
    $body .= "We were unable to process the payment for your order #" . $order_id . ".\n\n";
    $body .= "This could be due to:\n";
    $body .= "- Insufficient funds in your account\n";
    $body .= "- Card expiry or incorrect card details\n";
    $body .= "- Bank security blocks\n\n";
    $body .= "Please try placing your order again or contact your bank to resolve any issues.\n\n";
    $body .= "If you need assistance, our support team is here to help.\n\n";
    $body .= "Regards,\nThe Dija Accessories Team\n";
    $body .= "support@accessoriesbydija.uk";

    // Use PHPMailer SMTP
    $sent = send_email_smtp($to, $subject, $body);

    // Log to DB
    try {
        $logStmt = $pdo->prepare('INSERT INTO email_logs (order_id, to_email, subject, sent, created_at) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->execute([$order_id, $to, $subject, $sent ? 1 : 0]);
    } catch (Exception $e) {
        // ignore logging errors
    }

    return $sent;
}
?>