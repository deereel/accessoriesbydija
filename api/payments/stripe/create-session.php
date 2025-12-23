<?php
/**
 * Stripe Payment Session Creation
 * GET /api/payments/stripe/create-session.php?order_id=123
 * 
 * Creates a Stripe Checkout Session
 * 
 * SETUP INSTRUCTIONS:
 * 1. Create Stripe account: https://stripe.com
 * 2. Get API keys from dashboard (publishable and secret)
 * 3. Set environment variables:
 *    - STRIPE_PUBLISHABLE_KEY (for frontend)
 *    - STRIPE_SECRET_KEY (for backend)
 * 4. Install Stripe PHP library:
 *    composer require stripe/stripe-php
 * 5. Configure webhook in Stripe dashboard:
 *    POST https://yourdomain.com/api/payments/stripe/webhook.php
 *    Events: checkout.session.completed, payment_intent.succeeded
 * 6. Copy webhook signing secret:
 *    STRIPE_WEBHOOK_SECRET
 * 
 * CURRENCY: 'gbp' (lowercase), amounts in pence (GBP * 100)
 */

session_start();
header('Content-Type: application/json');

// Load environment variables from .env file
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../config/database.php';

// TODO: Use Composer autoloader if installed
// require __DIR__ . '/../../../vendor/autoload.php';

// Get order ID
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    // Fetch order - Remove status check to allow any order to be paid
    $stmt = $pdo->prepare("SELECT o.* FROM orders o WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        error_log("Order not found: $order_id");
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    error_log("Order found - ID: $order_id, Total: " . $order['total_amount'] . ", Email: " . $order['email']);

    // Validate required order fields
    if (empty($order['total_amount']) || empty($order['email'])) {
        http_response_code(400);
        error_log("Missing required order fields - Total: " . ($order['total_amount'] ?? 'NULL') . ", Email: " . ($order['email'] ?? 'NULL'));
        echo json_encode(['success' => false, 'message' => 'Order is missing required fields']);
        exit;
    }

    // TODO: Get STRIPE_SECRET_KEY from environment
    $STRIPE_SECRET_KEY = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_your_secret_key_here';

    if (empty($STRIPE_SECRET_KEY) || strpos($STRIPE_SECRET_KEY, 'your_secret_key') !== false || strpos($STRIPE_SECRET_KEY, 'xxxxx') !== false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Stripe configuration incomplete']);
        exit;
    }

    // TODO: If using Stripe PHP library
    // \Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);

    // Prepare line items from order_items table
    $line_items = [];
    
    // TODO: Fetch actual order items
    // For now, create a single item with total amount
    $order_number = $order['order_number'] ?? 'Order #' . $order['id'];
    $line_items[] = [
        'price_data' => [
            'currency' => 'gbp',
            'product_data' => [
                'name' => $order_number,
                'description' => 'Order items'
            ],
            'unit_amount' => intval($order['total_amount'] * 100) // Amount in pence
        ],
        'quantity' => 1
    ];

    // TODO: Add shipping as a separate line item
    // 'name' => 'Shipping',
    // 'unit_amount' => intval($order['shipping_amount'] * 100)

    // Create Stripe Checkout Session
    // Determine the protocol (http or https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host;
    
    $session_params = [
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => $base_url . '/order-confirmation.php?order_id=' . $order_id . '&payment=stripe&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $base_url . '/checkout.php?cancelled=1',
        'customer_email' => $order['email'],
        'metadata' => [
            'order_id' => $order_id
        ]
    ];

    // Make API request to Stripe
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.stripe.com/v1/checkout/sessions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($session_params),
        CURLOPT_USERPWD => $STRIPE_SECRET_KEY . ':',
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log for debugging
    error_log("Stripe API Response - HTTP Code: $http_code, Response: " . substr($response, 0, 500));

    if ($curl_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Curl error: ' . $curl_error]);
        exit;
    }

    if ($http_code !== 200) {
        http_response_code(500);
        $error_response = json_decode($response, true);
        $error_message = $error_response['error']['message'] ?? 'Failed to create Stripe session';
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    $session = json_decode($response, true);

    if (!isset($session['id'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid Stripe response']);
        exit;
    }

    // Store session ID in session for verification
    $_SESSION['stripe_session_id'] = $session['id'];

    // Redirect to Stripe Checkout
    header('Location: ' . $session['url']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
