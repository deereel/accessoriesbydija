<?php
/**
 * Remita Payment Verification
 * GET/POST /api/payments/remita/verify.php?rrr=XXX
 * 
 * Verifies Remita payment and updates order status
 * Called after customer completes payment (redirect from Remita)
 * Also handles webhook from Remita
 * 
 * SECURITY:
 * - Always verify payment on server side using RRR
 * - Validate RRR exists in your database
 * - Check amount matches exactly
 * - For webhooks, verify Remita signature if provided
 * - Prevent duplicate processing with idempotent checks
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

// TODO: Get credentials from environment
$REMITA_MERCHANT_ID = getenv('REMITA_MERCHANT_ID') ?: '';
$REMITA_API_KEY = getenv('REMITA_API_KEY') ?: '';

if (!$REMITA_MERCHANT_ID || !$REMITA_API_KEY) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Remita configuration incomplete']);
    exit;
}

// Get RRR from query parameter or session
$rrr = $_GET['rrr'] ?? $_POST['rrr'] ?? $_SESSION['remita_rrr'] ?? null;

if (!$rrr) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'RRR is required']);
    exit;
}

try {
    // Find order by RRR in notes
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE notes LIKE ? AND status = 'pending' LIMIT 1");
    $stmt->execute(['%RRR: ' . $rrr . '%']);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Verify payment with Remita API
    // Generate API HASH for request
    $hash_input = implode('|', [
        $REMITA_MERCHANT_ID,
        $rrr,
        $REMITA_API_KEY
    ]);
    $api_hash = hash('sha512', $hash_input);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://remita.net/api/v1/send/api/echopay/query/querybyrrnandmerchantid/' . $REMITA_MERCHANT_ID . '/' . $rrr . '/' . $api_hash,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
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

    $remita_response = json_decode($response, true);

    // Check response status
    if (!isset($remita_response['responseCode']) || $remita_response['responseCode'] !== '00') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment verification failed: ' . ($remita_response['responseDescription'] ?? 'Unknown error')]);
        exit;
    }

    $transaction = $remita_response['data'] ?? [];

    // Validate transaction status
    if ($transaction['transactionStatus'] !== 'Approved' && $transaction['transactionStatus'] !== 'Completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment not approved']);
        exit;
    }

    // SECURITY: Verify amount matches exactly (in GBP)
    $expected_amount = floatval($order['total_amount']);
    $received_amount = floatval($transaction['amount'] ?? 0);
    
    // Allow small floating point differences
    if (abs($expected_amount - $received_amount) > 0.01) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment amount mismatch']);
        exit;
    }

    // Verify order integrity: check if discount and total are reasonable
    $itemsStmt = $pdo->prepare("SELECT SUM(quantity * unit_price) as item_subtotal FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$order['id']]);
    $itemResult = $itemsStmt->fetch();
    $item_subtotal = $itemResult ? floatval($itemResult['item_subtotal'] ?? 0) : 0;
    
    // If order items exist, verify totals are consistent
    if ($item_subtotal > 0) {
        $calculated_total = $item_subtotal + floatval($order['shipping_amount']) - floatval($order['discount_amount']);
        $calculated_total = round($calculated_total, 2);
        
        // Allow small tolerance for rounding
        if (abs($calculated_total - floatval($order['total_amount'])) > 0.01) {
            error_log("Order amount mismatch for order_id={$order['id']}: calculated={$calculated_total}, recorded={$order['total_amount']}");
        }
    }

    // Update order status to paid
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ?, notes = ? WHERE id = ?");
    $stmt->execute([
        'processing',
        'paid',
        'Paid via Remita. RRR: ' . $rrr . ' | Transaction: ' . ($transaction['transactionId'] ?? 'N/A'),
        $order['id']
    ]);

    // TODO: Create order_items from order
    // TODO: Update inventory
    // TODO: Send confirmation email

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Redirect scenario
        $_SESSION['payment_success'] = true;
        $_SESSION['order_id'] = $order['id'];
        header('Location: /order-confirmation.php?order_id=' . $order['id']);
        exit;
    } else {
        // Webhook scenario
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'order_id' => $order['id'],
            'order_number' => $order['order_number']
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
