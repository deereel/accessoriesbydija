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
    $body = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>New Order Received</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #f8f9fa; padding: 20px; border-bottom: 2px solid #dee2e6; }
        .order-details { margin: 20px 0; }
        .product { border: 1px solid #dee2e6; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .product-image { max-width: 100px; max-height: 100px; margin-right: 15px; float: left; }
        .product-info { margin-left: 120px; }
        .product-name { font-weight: bold; font-size: 16px; margin-bottom: 5px; }
        .product-description { color: #666; font-size: 14px; margin-bottom: 10px; }
        .product-details { font-size: 12px; color: #888; margin-bottom: 10px; }
        .variant-info { background-color: #f8f9fa; padding: 8px; border-radius: 3px; margin-bottom: 10px; }
        .price-info { font-weight: bold; color: #28a745; }
        .clear { clear: both; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='header'>
        <h2>New Order Received</h2>
        <p>A new order has been placed and requires your attention.</p>
    </div>

    <div class='order-details'>
        <h3>Order Information</h3>
        <p><strong>Order ID:</strong> {$order_id}</p>
        <p><strong>Order Number:</strong> {$order['order_number']}</p>
        <p><strong>Customer:</strong> " . ($order['first_name'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Guest') . "</p>
        <p><strong>Email:</strong> {$order['email']}</p>
        <p><strong>Total Amount:</strong> £" . number_format($order['total_amount'], 2) . "</p>
        <p><strong>Payment Method:</strong> " . htmlspecialchars($order['payment_method'] ?? 'N/A') . "</p>
        <p><strong>Order Date:</strong> " . date('Y-m-d H:i:s') . "</p>
    </div>

    <h3>Order Items</h3>";

    foreach ($items as $item) {
        $images = array_filter(explode(',', $item['images'] ?? ''));
        $primary_image = !empty($images[0]) ? $base_url . '/' . $images[0] : 'https://via.placeholder.com/100x100?text=No+Image';

        // Build variant information
        $variant_info = [];
        if (!empty($item['material_name'])) $variant_info[] = "Material: " . htmlspecialchars($item['material_name']);
        if (!empty($item['color'])) $variant_info[] = "Color: " . htmlspecialchars($item['color']);
        if (!empty($item['adornment'])) $variant_info[] = "Adornment: " . htmlspecialchars($item['adornment']);
        if (!empty($item['size'])) $variant_info[] = "Size: " . htmlspecialchars($item['size']);
        if (!empty($item['variation_tag'])) $variant_info[] = "Variant: " . htmlspecialchars($item['variation_tag']);

        $body .= "<div class='product'>
            <img src='{$primary_image}' alt='" . htmlspecialchars($item['product_name']) . "' class='product-image'>
            <div class='product-info'>
                <div class='product-name'>" . htmlspecialchars($item['product_name']) . "</div>
                <div class='product-description'>" . htmlspecialchars($item['short_description'] ?? $item['description'] ?? 'No description available') . "</div>";

        if (!empty($variant_info)) {
            $body .= "<div class='variant-info'>" . implode(' | ', $variant_info) . "</div>";
        }

        $body .= "<div class='product-details'>
                    <strong>SKU:</strong> " . htmlspecialchars($item['product_sku']) . "
                </div>
                <div class='price-info'>
                    Quantity: {$item['quantity']} |
                    Unit Price: £" . number_format($item['unit_price'], 2) . " |
                    Total: £" . number_format($item['total_price'], 2) . "
                </div>
            </div>
            <div class='clear'></div>
        </div>";
    }

    $body .= "<div class='footer'>
        <p><strong>Please process this order promptly.</strong></p>
        <p>This email was sent automatically from the Dija Accessories order system.</p>
    </div>
</body>
</html>";

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

function send_admin_refund_notification($pdo, $order_id, $amount, $reason) {
    // Fetch order details
    $stmt = $pdo->prepare("SELECT o.*, c.first_name, c.last_name, c.email AS customer_email
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $admin_emails = ['biodunoladayo@gmail.com', 'accessoriesbydija@gmail.com'];
    $subject = "Refund Processed - Order #" . $order_id;

    $body = "A refund has been processed.\n\n";
    $body .= "Order Details:\n";
    $body .= "Order ID: " . $order_id . "\n";
    $body .= "Order Number: " . $order['order_number'] . "\n";
    $body .= "Customer: " . ($order['first_name'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Guest') . "\n";
    $body .= "Email: " . $order['email'] . "\n";
    $body .= "Refund Amount: £" . number_format($amount, 2) . "\n";
    $body .= "Reason: " . htmlspecialchars($reason) . "\n\n";
    $body .= "Please note this refund in your records.\n";

    return send_email_smtp($admin_emails, $subject, $body);
}
?>