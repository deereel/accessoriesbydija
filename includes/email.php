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
        if (is_array($to)) {
            foreach ($to as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($to);
        }

        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($is_html) {
            $mail->AltBody = strip_tags($body);
        }

        $to_display = is_array($to) ? implode(', ', $to) : $to;
        error_log("Attempting to send email to $to_display with subject: $subject");
        $mail->send();
        error_log("Email sent successfully to $to_display");
        return true;
    } catch (Exception $e) {
        $error_message = $mail->ErrorInfo;
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

function send_order_confirmation_email($pdo, $order_id) {
    // Check if email was already sent
    $checkStmt = $pdo->prepare("SELECT id FROM email_logs WHERE order_id = ? AND subject LIKE 'Order Confirmation%' AND sent = 1 LIMIT 1");
    $checkStmt->execute([$order_id]);
    if ($checkStmt->fetch()) {
        error_log("Order confirmation email already sent for order {$order_id}");
        return true; // Already sent
    }

    // Fetch order and customer details
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $to = $order['customer_email'] ?? null;
    if (!$to) return false; // no email to send to

    // Fetch items with variant details
    $it = $pdo->prepare("SELECT oi.quantity, oi.unit_price, oi.material_name, oi.color, oi.adornment, oi.size, oi.variation_tag, p.name
        FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $it->execute([$order_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);

    $subject = "Order Confirmation - Order #" . $order['order_number'];

    // Helper function to format variant details
    function format_variant_details($item) {
        $details = [];
        if (!empty($item['material_name'])) $details[] = 'Material: ' . htmlspecialchars($item['material_name']);
        if (!empty($item['color'])) $details[] = 'Color: ' . htmlspecialchars($item['color']);
        if (!empty($item['adornment'])) $details[] = 'Adornment: ' . htmlspecialchars($item['adornment']);
        if (!empty($item['size'])) $details[] = 'Size: ' . htmlspecialchars($item['size']);
        if (!empty($item['variation_tag'])) $details[] = 'Variant: ' . htmlspecialchars($item['variation_tag']);
        return $details ? ' (' . implode(', ', $details) . ')' : '';
    }

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .order-details { border: 1px solid #ddd; padding: 15px; margin: 20px 0; }
        .order-item { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .order-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .item-name { font-weight: bold; }
        .item-details { color: #666; font-size: 0.9em; margin-left: 10px; }
        .item-price { text-align: right; font-weight: bold; }
        .total { font-weight: bold; font-size: 18px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dija Accessories</h1>
            <h2>Order Confirmation</h2>
        </div>
        <div class="content">
            <p>Hello ' . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ',</p>
            <p>Thank you for your order. Your order number is <strong>#' . $order['order_number'] . '</strong>.</p>
            <div class="order-details">
                <h3>Order Details:</h3>
                <div>';
    foreach ($items as $row) {
        $variant_details = format_variant_details($row);
        $body .= '<div class="order-item">
                    <div class="item-name">' . htmlspecialchars($row['name']) . $variant_details . '</div>
                    <div class="item-details">Quantity: ' . intval($row['quantity']) . ' @ £' . number_format($row['unit_price'], 2) . ' each</div>
                    <div class="item-price">£' . number_format($row['quantity'] * $row['unit_price'], 2) . '</div>
                  </div>';
    }
    // Calculate subtotal from total + discount - shipping
    $subtotal = ($order['total_amount'] ?? 0) + ($order['discount_amount'] ?? 0) - ($order['shipping_amount'] ?? 0);

    $body .= '</div>
                <p><strong>Subtotal:</strong> £' . number_format($subtotal, 2) . '</p>
                <p><strong>Shipping:</strong> £' . number_format($order['shipping_amount'] ?? 0, 2) . '</p>
                <p><strong>Discount:</strong> -£' . number_format($order['discount_amount'] ?? 0, 2) . '</p>
                <p class="total">Total: £' . number_format($order['total_amount'] ?? 0, 2) . '</p>
            </div>
            <p>We will send another email when your order ships.</p>
            <p>Regards,<br>The Dija Accessories Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    // Use PHPMailer SMTP with HTML
    $sent = send_email_smtp($to, $subject, $body, true);

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

    // Fetch order items with detailed product information and images
    $it = $pdo->prepare("SELECT oi.*, p.description, p.short_description,
                               GROUP_CONCAT(DISTINCT pi.image_url ORDER BY
                                   CASE WHEN oi.variation_id IS NOT NULL AND pi.variant_id = oi.variation_id THEN 0
                                        WHEN pi.variant_id IS NULL THEN 1
                                        ELSE 2 END,
                                   pi.is_primary DESC, pi.sort_order ASC) as images
                        FROM order_items oi
                        JOIN products p ON p.id = oi.product_id
                        LEFT JOIN product_images pi ON pi.product_id = p.id
                           AND (pi.variant_id IS NULL OR pi.variant_id = oi.variation_id)
                        WHERE oi.order_id = ?
                        GROUP BY oi.id");
    $it->execute([$order_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);

    $admin_emails = ['biodunoladayo@gmail.com', 'accessoriesbydija@gmail.com'];
    $subject = "New Order Received - Order #" . $order_id;

    // Get base URL for images
    $base_url = $_ENV['APP_URL'] ?? 'https://accessoriesbydija.uk';

    // Create HTML email body
    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Order Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #dc3545; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Dija Accessories</h1>
            <h2 style="margin: 0;">New Order Received</h2>
        </div>
        <div style="padding: 20px;">
            <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #dc3545;">
                <h3>Order Information:</h3>
                <p><strong>Order ID:</strong> ' . $order_id . '</p>
                <p><strong>Order Number:</strong> ' . $order['order_number'] . '</p>
                <p><strong>Customer:</strong> ' . ($order['first_name'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Guest') . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
                <p><strong>Total Amount:</strong> £' . number_format($order['total_amount'], 2) . '</p>
                <p><strong>Payment Method:</strong> ' . ($order['payment_method'] ?? 'N/A') . '</p>
                <p><strong>Order Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
            </div>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 20px 0;">
                <h3>Order Items:</h3>';
    foreach ($items as $item) {
        $body .= '<div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                    <strong>' . htmlspecialchars($item['product_name']) . '</strong> x ' . $item['quantity'] . ' @ £' . number_format($item['unit_price'], 2) . ' = £' . number_format($item['total_price'], 2) . '
                  </div>';
    }
    $body .= '</div>
            <p style="font-weight: bold; font-size: 18px; color: #dc3545;">Total: £' . number_format($order['total_amount'], 2) . '</p>
            <p>Please process this order promptly.</p>
            <p>This email was sent automatically from the Dija Accessories order system.</p>
        </div>
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px;">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    return send_email_smtp($admin_emails, $subject, $body, true);
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

    $subject = "Your Order Has Shipped - Order #" . $order['order_number'];

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Shipped</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #28a745; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Dija Accessories</h1>
            <h2 style="margin: 0;">Your Order Has Shipped!</h2>
        </div>
        <div style="padding: 20px;">
            <p>Hello ' . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ',</p>
            <p>Great news! Your order <strong>#' . $order['order_number'] . '</strong> has been shipped and is on its way to you.</p>';

    if ($tracking_number && $carrier) {
        $body .= '<div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
                    <h3>Tracking Information:</h3>
                    <p><strong>Carrier:</strong> ' . htmlspecialchars($carrier) . '</p>
                    <p><strong>Tracking Number:</strong> ' . htmlspecialchars($tracking_number) . '</p>
                    <p>You can track your package at the carrier\'s website using the tracking number above.</p>
                  </div>';
    }

    $body .= '<p><strong>Expected Delivery:</strong> 3-5 business days from shipping date</p>
            <p>If you have any questions about your order, please don\'t hesitate to contact us.</p>
            <p>Thank you for shopping with Dija Accessories!</p>
            <p>Regards,<br>The Dija Accessories Team<br>support@accessoriesbydija.uk</p>
        </div>
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px;">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    // Use PHPMailer SMTP with HTML
    $sent = send_email_smtp($to, $subject, $body, true);

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

    $subject = "Your Order Has Been Delivered - Order #" . $order['order_number'];

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Delivered</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #17a2b8; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .highlight { background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dija Accessories</h1>
            <h2>Your Order Has Been Delivered!</h2>
        </div>
        <div class="content">
            <p>Hello ' . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ',</p>
            <p>Your order <strong>#' . $order['order_number'] . '</strong> has been successfully delivered!</p>
            <div class="highlight">
                <p>We hope you love your new jewelry pieces. If you have any questions or need assistance, please don\'t hesitate to contact us.</p>
            </div>
            <p>Thank you for choosing Dija Accessories for your jewelry needs.</p>
            <p>We\'d love to hear from you! Please consider leaving a review of your purchase.</p>
            <p>Regards,<br>The Dija Accessories Team<br>support@accessoriesbydija.uk</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    // Use PHPMailer SMTP with HTML
    $sent = send_email_smtp($to, $subject, $body, true);

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

    $subject = "Order Cancelled - Order #" . $order['order_number'];

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Cancelled</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #6c757d; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Dija Accessories</h1>
            <h2 style="margin: 0;">Order Cancelled</h2>
        </div>
        <div style="padding: 20px;">
            <p>Hello ' . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ',</p>
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <p>We\'re writing to inform you that your order <strong>#' . $order['order_number'] . '</strong> has been cancelled.</p>
            </div>
            <p>If this cancellation was unexpected or if you have any questions, please don\'t hesitate to contact our support team.</p>
            <p>If you would like to place a new order, you can do so at any time on our website.</p>
            <p>We apologize for any inconvenience this may have caused.</p>
            <p>Regards,<br>The Dija Accessories Team<br>support@accessoriesbydija.uk</p>
        </div>
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px;">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    // Use PHPMailer SMTP with HTML
    $sent = send_email_smtp($to, $subject, $body, true);

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

    $subject = "Payment Failed - Order #" . $order['order_number'];

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Failed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #dc3545; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Dija Accessories</h1>
            <h2 style="margin: 0;">Payment Failed</h2>
        </div>
        <div style="padding: 20px;">
            <p>Hello ' . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ',</p>
            <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <p>We were unable to process the payment for your order <strong>#' . $order['order_number'] . '</strong>.</p>
            </div>
            <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #dc3545;">
                <p><strong>This could be due to:</strong></p>
                <ul>
                    <li>Insufficient funds in your account</li>
                    <li>Card expiry or incorrect card details</li>
                    <li>Bank security blocks</li>
                </ul>
            </div>
            <p>Please try placing your order again or contact your bank to resolve any issues.</p>
            <p>If you need assistance, our support team is here to help.</p>
            <p>Regards,<br>The Dija Accessories Team<br>support@accessoriesbydija.uk</p>
        </div>
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px;">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    // Use PHPMailer SMTP with HTML
    $sent = send_email_smtp($to, $subject, $body, true);

    // Log to DB
    try {
        $logStmt = $pdo->prepare('INSERT INTO email_logs (order_id, to_email, subject, sent, created_at) VALUES (?, ?, ?, ?, NOW())');
        $logStmt->execute([$order_id, $to, $subject, $sent ? 1 : 0]);
    } catch (Exception $e) {
        // ignore logging errors
    }

    return $sent;
}

function send_admin_refund_notification($pdo, $order_id, $amount, $reason) {
    // Fetch order details
    $stmt = $pdo->prepare("SELECT o.*, c.first_name, c.last_name, c.email AS customer_email
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $admin_emails = ['biodunoladayo@gmail.com', 'accessoriesbydija@gmail.com'];
    $subject = "Refund Processed - Order #" . $order_id;

    $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Refund Processed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #ffc107; color: #212529; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Dija Accessories</h1>
            <h2 style="margin: 0;">Refund Processed</h2>
        </div>
        <div style="padding: 20px;">
            <p>A refund has been processed.</p>
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h3>Order Details:</h3>
                <p><strong>Order ID:</strong> ' . $order_id . '</p>
                <p><strong>Order Number:</strong> ' . $order['order_number'] . '</p>
                <p><strong>Customer:</strong> ' . ($order['first_name'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Guest') . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
                <p><strong>Refund Amount:</strong> <span style="font-weight: bold; font-size: 18px; color: #856404;">£' . number_format($amount, 2) . '</span></p>
                <p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>
            </div>
            <p>Please note this refund in your records.</p>
        </div>
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px;">
            <p>&copy; 2024 Dija Accessories. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    return send_email_smtp($admin_emails, $subject, $body, true);
}
?>