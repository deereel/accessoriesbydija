<?php
/**
 * Paystack Payment Verification & Webhook Handler
 * POST /api/payments/paystack/verify.php
 * 
 * Verifies Paystack payment and updates order status
 * Called after customer completes payment (redirect from Paystack)
 * Also handles webhook POST from Paystack for asynchronous verification
 * 
 * SECURITY:
 * - Always verify payment on server side
 * - Validate reference exists in your database
 * - Check amount matches exactly (prevent price manipulation)
 * - For webhooks, verify Paystack signature using secret key
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

// TODO: Configure these from environment
$PAYSTACK_SECRET_KEY = getenv('PAYSTACK_SECRET_KEY') ?: 'sk_test_your_secret_key_here';

// Handle webhook verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the payment reference from Paystack webhook
    $input = file_get_contents('php://input');
    $event = json_decode($input);

    // TODO: Verify Paystack signature for webhook
    // $signature = hash_hmac('sha512', $input, $PAYSTACK_SECRET_KEY);
    // if ($signature !== $_SERVER['HTTP_X_PAYSTACK_SIGNATURE']) {
    //     http_response_code(403);
    //     exit;
    // }

    if ($event && isset($event->data->reference)) {
        $reference = $event->data->reference;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid webhook data']);
        exit;
    }
}
// Handle redirect verification (GET)
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $reference = $_GET['reference'] ?? $_SESSION['paystack_reference'] ?? null;

    if (!$reference) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment reference is required']);
        exit;
    }
}
else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Verify payment with Paystack API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.paystack.co/transaction/verify/' . urlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $PAYSTACK_SECRET_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to verify payment']);
        exit;
    }

    $paystack_response = json_decode($response, true);

    if (!$paystack_response['status']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        exit;
    }

    $transaction = $paystack_response['data'];

    // Validate payment status
    if ($transaction['status'] !== 'success') {
        // Payment not successful
        // TODO: Update order status to 'failed' or 'cancelled'
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment was not successful']);
        exit;
    }

    // Find order by reference (order_number)
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$transaction['reference']]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // SECURITY: Verify amount matches exactly (in kobo)
    $expected_amount = intval($order['total_amount'] * 100);
    if ($transaction['amount'] !== $expected_amount) {
        // Amount mismatch - potential fraud attempt
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment amount mismatch']);
        exit;
    }

    // TODO: Verify currency is correct

    // Update order status to paid
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?");
    $stmt->execute([
        'paid',
        'Paid via Paystack. Reference: ' . $reference,
        $order['id']
    ]);

    // TODO: Create order_items records from pending order
    // TODO: Update inventory/stock levels
    // TODO: Send confirmation email to customer

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'order_id' => $order['id'],
        'order_number' => $order['order_number']
    ]);

    // If this is a redirect (not webhook), also set session
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $_SESSION['payment_success'] = true;
        $_SESSION['order_id'] = $order['id'];
        
        // Redirect to order confirmation page
        header('Location: /order-confirmation.php?order_id=' . $order['id']);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
