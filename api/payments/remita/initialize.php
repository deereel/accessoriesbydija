<?php
/**
 * Remita Payment Initialize
 * GET /api/payments/remita/initialize.php?order_id=123
 * 
 * Generates Remita RRR and initiates payment
 * 
 * SETUP INSTRUCTIONS:
 * 1. Create Remita account: https://remita.net
 * 2. Register your business and get credentials:
 *    - REMITA_MERCHANT_ID
 *    - REMITA_API_KEY
 *    - REMITA_SERVICE_ID (for your payment service)
 *    - REMITA_PUBLIC_KEY (for client-side validation if needed)
 * 3. Set environment variables with your credentials
 * 4. Configure webhook callback in Remita dashboard:
 *    POST https://yourdomain.com/api/payments/remita/verify.php
 * 5. Test in sandbox first: https://remita.net/sandbox
 * 
 * RRR GENERATION:
 * - Unique Reference Number (RRR) is generated for each transaction
 * - RRR is required for payment verification
 * - Store RRR with order for webhook verification
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    // Fetch order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // TODO: Get credentials from environment
    $REMITA_MERCHANT_ID = getenv('REMITA_MERCHANT_ID') ?: '';
    $REMITA_API_KEY = getenv('REMITA_API_KEY') ?: '';
    $REMITA_SERVICE_ID = getenv('REMITA_SERVICE_ID') ?: '';

    if (!$REMITA_MERCHANT_ID || !$REMITA_API_KEY || !$REMITA_SERVICE_ID) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Remita configuration incomplete']);
        exit;
    }

    // Generate RRR (Remita Reference Number)
    // Format: [MERCHANT_ID][SERVICE_ID][UNIQUE_REFERENCE]
    // RRR must be unique and not already used
    do {
        $unique_ref = date('YmdHis') . rand(1000, 9999);
        $rrr = $REMITA_MERCHANT_ID . $REMITA_SERVICE_ID . $unique_ref;
        
        // Check if RRR is already used
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE notes LIKE ? LIMIT 1");
        $stmt->execute(['%RRR: ' . $rrr . '%']);
        $existing = $stmt->fetch();
    } while ($existing);

    // Prepare Remita payload
    $remita_payload = [
        'serviceTypeId' => $REMITA_SERVICE_ID,
        'amount' => $order['total_amount'], // Amount in GBP
        'orderId' => $order['order_number'],
        'payerName' => 'Customer Order',
        'payerEmail' => $order['email'],
        'payerPhone' => '', // TODO: Get from order if available
        'description' => 'Payment for Order ' . $order['order_number'],
        'currency' => 'GBP',
        'redirectUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/payments/remita/verify.php?rrr=' . $rrr
    ];

    // Generate API HASH for request authentication
    // hash = sha512(merchantId|serviceTypeId|orderId|amount|apiKey)
    $hash_input = implode('|', [
        $REMITA_MERCHANT_ID,
        $REMITA_SERVICE_ID,
        $order['order_number'],
        $order['total_amount'],
        $REMITA_API_KEY
    ]);
    $api_hash = hash('sha512', $hash_input);

    // Make request to Remita API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://remita.net/api/v1/send/api/echopay/payment/pay',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($remita_payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $REMITA_MERCHANT_ID . '|' . $api_hash
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to initialize Remita payment']);
        exit;
    }

    $remita_response = json_decode($response, true);

    if (!isset($remita_response['data']['paymentUrl']) || !$remita_response['data']['rrr']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid Remita response']);
        exit;
    }

    // Store RRR in order notes for verification
    $stmt = $pdo->prepare("UPDATE orders SET notes = ? WHERE id = ?");
    $stmt->execute([
        'RRR: ' . $remita_response['data']['rrr'] . ' | ' . $order['notes'],
        $order_id
    ]);

    // Store RRR in session for verification
    $_SESSION['remita_rrr'] = $remita_response['data']['rrr'];

    // Redirect to Remita payment page
    header('Location: ' . $remita_response['data']['paymentUrl']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
