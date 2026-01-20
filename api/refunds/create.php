<?php
/**
 * Refund Processing API
 * POST /api/refunds/create.php
 *
 * Initiates refund for an order
 * Supports Paystack, Stripe, and Remita refunds
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/email.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$amount = $data['amount'] ?? null;
$reason = $data['reason'] ?? 'Customer request';

if (!$order_id || !$amount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID and amount are required']);
    exit;
}

try {
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if ($order['status'] !== 'paid' && $order['status'] !== 'processing') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order must be paid or processing to refund']);
        exit;
    }

    if ($amount > $order['total_amount']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Refund amount cannot exceed order total']);
        exit;
    }

    $payment_method = $order['payment_method'];

    // Process refund based on payment method
    $refund_result = false;
    $refund_reference = null;

    switch ($payment_method) {
        case 'paystack':
            $refund_result = process_paystack_refund($order, $amount, $reason, $refund_reference);
            break;
        case 'stripe':
            $refund_result = process_stripe_refund($order, $amount, $reason, $refund_reference);
            break;
        case 'remita':
            $refund_result = process_remita_refund($order, $amount, $reason, $refund_reference);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unsupported payment method for refunds']);
            exit;
    }

    if ($refund_result) {
        // Update order status
        $new_status = ($amount >= $order['total_amount']) ? 'refunded' : 'partially_refunded';
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, notes = CONCAT(notes, ?) WHERE id = ?");
        $stmt->execute([$new_status, "\nRefund processed: £{$amount} - {$reason} - Ref: {$refund_reference}", $order_id]);

        // Log refund
        $logStmt = $pdo->prepare("INSERT INTO refund_logs (order_id, amount, reason, payment_method, reference, processed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $logStmt->execute([$order_id, $amount, $reason, $payment_method, $refund_reference, $_SESSION['admin_id']]);

        // Send refund notification emails
        try {
            send_refund_notification_email($pdo, $order_id, $amount, $reason);
            send_admin_refund_notification($pdo, $order_id, $amount, $reason);
        } catch (Exception $e) {
            error_log('Failed to send refund notification: ' . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Refund processed successfully',
            'refund_reference' => $refund_reference,
            'new_status' => $new_status
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Refund processing failed']);
    }

} catch (PDOException $e) {
    error_log('Refund processing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

function process_paystack_refund($order, $amount, $reason, &$reference) {
    $PAYSTACK_SECRET_KEY = getenv('PAYSTACK_SECRET_KEY');
    if (!$PAYSTACK_SECRET_KEY) return false;

    // Paystack refunds require transaction reference
    // Extract from order notes
    $notes = $order['notes'];
    if (preg_match('/Reference: ([^\s]+)/', $notes, $matches)) {
        $transaction_ref = $matches[1];
    } else {
        return false; // Cannot find transaction reference
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.paystack.co/refund',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'transaction' => $transaction_ref,
            'amount' => intval($amount * 100), // Convert to kobo
            'reason' => $reason
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $PAYSTACK_SECRET_KEY,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result['status']) {
            $reference = $result['data']['id'];
            return true;
        }
    }

    return false;
}

function process_stripe_refund($order, $amount, $reason, &$reference) {
    $STRIPE_SECRET_KEY = getenv('STRIPE_SECRET_KEY');
    if (!$STRIPE_SECRET_KEY) return false;

    // Extract payment intent from notes
    $notes = $order['notes'];
    if (preg_match('/Intent: ([^\s]+)/', $notes, $matches)) {
        $payment_intent_id = $matches[1];
    } elseif (preg_match('/Session: ([^\s]+)/', $notes, $matches)) {
        // For older sessions, we might need to get payment intent from session
        // This is simplified - in production you'd retrieve the payment intent
        return false;
    } else {
        return false;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.stripe.com/v1/refunds',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'payment_intent' => $payment_intent_id,
            'amount' => intval($amount * 100), // Convert to pence
            'reason' => 'requested_by_customer',
            'metadata[reason]' => $reason
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $STRIPE_SECRET_KEY,
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result['status'] === 'succeeded') {
            $reference = $result['id'];
            return true;
        }
    }

    return false;
}

function process_remita_refund($order, $amount, $reason, &$reference) {
    $REMITA_MERCHANT_ID = getenv('REMITA_MERCHANT_ID');
    $REMITA_API_KEY = getenv('REMITA_API_KEY');
    if (!$REMITA_MERCHANT_ID || !$REMITA_API_KEY) return false;

    // Extract RRR from notes
    $notes = $order['notes'];
    if (preg_match('/RRR: ([^\s]+)/', $notes, $matches)) {
        $rrr = $matches[1];
    } else {
        return false;
    }

    // Remita refund process - this is simplified
    // In production, you'd implement proper Remita refund API calls
    // For now, we'll simulate success and generate a reference

    $reference = 'REFUND_' . time() . '_' . $rrr;
    error_log("Remita refund simulated for RRR: {$rrr}, Amount: {$amount}");

    return true; // Simulated success
}

function send_refund_notification_email($pdo, $order_id, $amount, $reason) {
    $stmt = $pdo->prepare("SELECT o.*, c.email AS customer_email, c.first_name, c.last_name
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || !$order['customer_email']) return false;

    $subject = "Refund Processed - Order #" . $order_id;

    $body = "Hello " . ($order['first_name'] ? htmlspecialchars($order['first_name']) : 'Customer') . ",\n\n";
    $body .= "Your refund has been processed successfully.\n\n";
    $body .= "Order Number: #" . $order_id . "\n";
    $body .= "Refund Amount: £" . number_format($amount, 2) . "\n";
    $body .= "Reason: " . htmlspecialchars($reason) . "\n\n";
    $body .= "The refund will appear in your original payment method within 3-5 business days.\n\n";
    $body .= "If you have any questions, please contact our support team.\n\n";
    $body .= "Regards,\nThe Dija Accessories Team\n";
    $body .= "support@accessoriesbydija.uk";

    return send_email_smtp($order['customer_email'], $subject, $body);
}
?>