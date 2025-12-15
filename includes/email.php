<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Minimal transactional email helper. Uses PHP mail() by default.
// For production, prefer using SMTP via PHPMailer or an external provider API.

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
    $body .= "\nSubtotal: £" . number_format($order['subtotal_amount'] ?? 0, 2) . "\n";
    $body .= "Shipping: £" . number_format($order['shipping_amount'] ?? 0, 2) . "\n";
    $body .= "Discount: -£" . number_format($order['discount_amount'] ?? 0, 2) . "\n";
    $body .= "Total: £" . number_format($order['total_amount'] ?? 0, 2) . "\n\n";
    $body .= "We will send another email when your order ships.\n\n";
    $body .= "Regards,\nThe Team\n";

    $headers = "From: no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'example.com') . "\r\n";
    $headers .= "Reply-To: support@" . ($_SERVER['SERVER_NAME'] ?? 'example.com') . "\r\n";

    // Use mail(); return boolean based on success. In production, replace with PHPMailer/SMTP.
    $sent = @mail($to, $subject, $body, $headers);

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
    $hdr = "From: no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'example.com') . "\r\n";
    foreach ($headers as $k => $v) {
        $hdr .= "$k: $v\r\n";
    }
    return @mail($to, $subject, $body, $hdr);
}
?>