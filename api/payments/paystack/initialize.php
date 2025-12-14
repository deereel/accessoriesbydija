<?php
/**
 * Paystack Payment Initialize
 * GET /api/payments/paystack/initialize.php?order_id=123
 * 
 * Initializes a Paystack transaction
 * 
 * SETUP INSTRUCTIONS:
 * 1. Create Paystack account: https://paystack.com
 * 2. Get your API keys from dashboard
 * 3. Set environment variables or config:
 *    - PAYSTACK_PUBLIC_KEY (used in frontend)
 *    - PAYSTACK_SECRET_KEY (used in backend)
 * 4. Configure webhook callback in Paystack dashboard:
 *    POST https://yourdomain.com/api/payments/paystack/verify.php
 * 5. For GBP: Paystack uses kobo (GBP * 100) for amounts
 * 
 * PAYSTACK RESPONSE CODES:
 * - charge.success: Payment completed
 * - charge.failed: Payment failed
 * - charge.dispute: Dispute raised
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

// Get order ID from query parameter
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    // Fetch order from database
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found or already processed']);
        exit;
    }

    // TODO: Set these from environment variables
    // putenv() or $_ENV via .env file
    $PAYSTACK_SECRET_KEY = getenv('PAYSTACK_SECRET_KEY') ?: 'sk_test_your_secret_key_here';
    
    // Validate secret key is set
    if (strpos($PAYSTACK_SECRET_KEY, 'your_secret_key') !== false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Paystack configuration incomplete']);
        exit;
    }

    // Prepare Paystack request
    // Amount should be in kobo (GBP * 100)
    $amount_in_kobo = intval($order['total_amount'] * 100);

    $paystack_params = [
        'email' => $order['email'],
        'amount' => $amount_in_kobo,
        'currency' => 'GBP',
        'reference' => $order['order_number'],
        'metadata' => [
            'order_id' => $order_id,
            'customer_id' => $order['customer_id'],
            'order_number' => $order['order_number']
        ]
    ];

    // Initialize Paystack transaction
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.paystack.co/transaction/initialize',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($paystack_params),
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
        echo json_encode(['success' => false, 'message' => 'Failed to initialize payment']);
        exit;
    }

    $paystack_response = json_decode($response, true);

    if (!$paystack_response['status']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $paystack_response['message'] ?? 'Paystack error']);
        exit;
    }

    // Store payment reference in session for verification
    $_SESSION['paystack_reference'] = $paystack_response['data']['reference'];

    // Redirect to Paystack checkout
    header('Location: ' . $paystack_response['data']['authorization_url']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
