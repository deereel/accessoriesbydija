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
    // Fetch order
    $stmt = $pdo->prepare("SELECT o.*, oi.* FROM orders o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id 
                           WHERE o.id = ? AND o.status = 'pending'");
    $stmt->execute([$order_id]);
    $order_data = $stmt->fetchAll();

    if (empty($order_data)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $order = $order_data[0]; // Main order details

    // TODO: Get STRIPE_SECRET_KEY from environment
    $STRIPE_SECRET_KEY = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_your_secret_key_here';

    if (strpos($STRIPE_SECRET_KEY, 'your_secret_key') !== false) {
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
    $line_items[] = [
        'price_data' => [
            'currency' => 'gbp',
            'product_data' => [
                'name' => 'Order #' . $order['order_number'],
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
    $session_params = [
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/payments/stripe/webhook.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout.php?cancelled=1',
        'customer_email' => $order['email'],
        'metadata' => [
            'order_id' => $order_id,
            'order_number' => $order['order_number']
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create Stripe session']);
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
