<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';

/**
 * POST /api/orders/create.php
 * Creates a pending order and prepares for payment
 * 
 * Expected JSON:
 * {
 *   "email": "customer@example.com",
 *   "address_id": 123,  (optional if logged in)
 *   "payment_method": "paystack|stripe|remita",
 *   "promo_code": "DISCOUNT20"
 * }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['email']) || empty($data['payment_method'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $customer_id = $_SESSION['customer_id'] ?? null;
    $email = $data['email'];
    $payment_method = $data['payment_method'];
    $address_id = $data['address_id'] ?? null;
    $promo_code = $data['promo_code'] ?? null;

    // TODO: Validate payment method is one of the accepted types
    if (!in_array($payment_method, ['paystack', 'stripe', 'remita'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
        exit;
    }

    // Fetch cart items from database or receive from client
    // For now, assume cart is in session or will be passed
    $cart_items = [];
    $subtotal = 0;

    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.price, p.name FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.customer_id = ?");
        $stmt->execute([$customer_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // TODO: Handle guest cart - client should pass cart items
        // For now, return error
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Guest checkout not yet implemented - please log in']);
        exit;
    }

    if (empty($cart_items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    // Calculate subtotal
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    // TODO: Validate and apply promo code
    $discount = 0;
    if ($promo_code) {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes 
                               WHERE code = ? AND is_active = 1 
                               AND start_date <= NOW() 
                               AND end_date >= NOW()");
        $stmt->execute([$promo_code]);
        $promo = $stmt->fetch();
        
        if ($promo) {
            if ($subtotal >= $promo['min_order_amount']) {
                if ($promo['type'] === 'percent') {
                    $discount = $subtotal * ($promo['value'] / 100);
                    if ($promo['max_discount'] && $discount > $promo['max_discount']) {
                        $discount = $promo['max_discount'];
                    }
                } elseif ($promo['type'] === 'amount') {
                    $discount = min($promo['value'], $subtotal);
                }
                
                // Update usage count
                $pdo->prepare("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE id = ?")->execute([$promo['id']]);
            }
        }
    }

    // Calculate totals
    $shipping = 5.00; // Fixed shipping cost
    $total_amount = $subtotal + $shipping - $discount;

    // Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Create pending order record
    // TODO: Ensure your orders table has these columns:
    // id, customer_id, order_number, email, total_amount, shipping_amount, discount_amount, 
    // status (pending/paid/processing/shipped/delivered/cancelled), 
    // payment_method, address_id, notes, created_at, updated_at
    
    $stmt = $pdo->prepare("INSERT INTO orders 
                           (customer_id, order_number, email, total_amount, shipping_amount, discount_amount, 
                            status, payment_method, address_id, notes) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $customer_id,
        $order_number,
        $email,
        $total_amount,
        $shipping,
        $discount,
        'pending',        // Status: pending payment
        $payment_method,
        $address_id,
        'Promo: ' . ($promo_code ?: 'None')
    ]);

    $order_id = $pdo->lastInsertId();

    // TODO: Create order_items table to store individual items
    // For now, just store basic structure
    // INSERT INTO order_items (order_id, product_id, quantity, price) ...

    // Store order in session for payment verification
    $_SESSION['pending_order'] = [
        'order_id' => $order_id,
        'order_number' => $order_number,
        'total_amount' => $total_amount,
        'currency' => 'GBP'
    ];

    // Clear cart for the customer
    if ($customer_id) {
        $pdo->prepare("DELETE FROM cart WHERE customer_id = ?")->execute([$customer_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'total_amount' => $total_amount
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
