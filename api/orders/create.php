<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';

/**
 * POST /api/orders/create.php
 * Creates a pending order and prepares for payment with server-side validation
 * 
 * Expected JSON:
 * {
 *   "email": "customer@example.com",
 *   "payment_method": "paystack|stripe|remita",
 *   "promo_code": "DISCOUNT20" (optional),
 *   "client_discount": 5.00 (optional - client-provided discount for verification),
 *   
 *   // For logged-in customers:
 *   "address_id": 123,
 *   
 *   // For guest checkout:
 *   "cart_items": [
 *     { "product_id": 1, "product_name": "Necklace", "quantity": 1, "price": 25.00, "sku": "SKU001" }
 *   ],
 *   "guest_address": {
 *     "first_name": "John",
 *     "last_name": "Doe",
 *     "address_line_1": "123 Main St",
 *     "address_line_2": "",
 *     "city": "London",
 *     "state": "London",
 *     "postal_code": "SW1A 1AA",
 *     "country": "United Kingdom"
 *   }
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
        echo json_encode(['success' => false, 'message' => 'Missing required fields: email and payment_method']);
        exit;
    }

    $customer_id = $_SESSION['customer_id'] ?? null;
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    $payment_method = strtolower(trim($data['payment_method']));
    
    // Validate payment method
    if (!in_array($payment_method, ['paystack', 'stripe', 'remita'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment method. Must be paystack, stripe, or remita']);
        exit;
    }

    $promo_code = !empty($data['promo_code']) ? strtoupper(trim($data['promo_code'])) : null;
    $client_discount = isset($data['client_discount']) ? floatval($data['client_discount']) : 0.0;
    $address_id = $data['address_id'] ?? null;
    $cart_items = [];
    $subtotal = 0;

    // Fetch cart items
    if ($customer_id) {
        // Logged-in customer: fetch from database
        $stmt = $pdo->prepare("SELECT c.id, c.product_id, c.quantity, p.price, p.name, p.sku 
                               FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.customer_id = ?");
        $stmt->execute([$customer_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Validate address_id if provided
        if ($address_id) {
            $addrStmt = $pdo->prepare("SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ?");
            $addrStmt->execute([$address_id, $customer_id]);
            if (!$addrStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid address for this customer']);
                exit;
            }
        }
    } else {
        // Guest checkout: use cart_items from request
        if (empty($data['cart_items']) || !is_array($data['cart_items'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cart items are required for guest checkout']);
            exit;
        }
        
        // Validate guest address
        if (empty($data['guest_address'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Shipping address is required for guest checkout']);
            exit;
        }
        
        $guest_addr = $data['guest_address'];
        $required_addr_fields = ['first_name', 'last_name', 'address_line_1', 'city', 'state', 'postal_code', 'country'];
        foreach ($required_addr_fields as $field) {
            if (empty($guest_addr[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing address field: $field"]);
                exit;
            }
        }
        
        $cart_items = $data['cart_items'];
    }

    if (empty($cart_items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    // Validate cart items and calculate subtotal
    foreach ($cart_items as $item) {
        if (empty($item['product_id']) || empty($item['quantity']) || !isset($item['price'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid cart item structure']);
            exit;
        }
        
        $qty = intval($item['quantity']);
        $price = floatval($item['price']);
        
        if ($qty <= 0 || $price < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid quantity or price in cart']);
            exit;
        }
        
        $subtotal += $price * $qty;
    }

    $subtotal = round($subtotal, 2);

    // ===== SERVER-SIDE PROMO VALIDATION =====
    $discount = 0.0;
    $promo_id = null;
    
    if ($promo_code) {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes 
                               WHERE code = ? AND is_active = 1 
                               AND start_date <= NOW() 
                               AND end_date >= NOW()");
        $stmt->execute([$promo_code]);
        $promo = $stmt->fetch();
        
        if (!$promo) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Promo code not found or expired']);
            exit;
        }
        
        // Check usage limit
        if ($promo['usage_limit'] && $promo['usage_count'] >= $promo['usage_limit']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Promo code limit exceeded']);
            exit;
        }
        
        // Check minimum order amount
        $min_order = floatval($promo['min_order_amount'] ?? 0);
        if ($min_order > 0 && $subtotal < $min_order) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Order does not meet minimum amount (£{$min_order}) for this promo"]);
            exit;
        }
        
        // Calculate server-side discount
        $type = $promo['type'];
        $value = floatval($promo['value']);
        $max_discount = isset($promo['max_discount']) && $promo['max_discount'] ? floatval($promo['max_discount']) : null;
        
        if ($type === 'percent') {
            $discount = ($subtotal * ($value / 100.0));
            if ($max_discount && $discount > $max_discount) {
                $discount = $max_discount;
            }
        } else {
            // Fixed amount discount
            $discount = min($value, $subtotal);
        }
        
        // Cap discount to subtotal
        $discount = round(min($discount, $subtotal), 2);
        $promo_id = $promo['id'];
        
        // Verify client-provided discount matches server calculation (within tolerance for rounding)
        $tolerance = 0.01;
        if (abs($client_discount - $discount) > $tolerance) {
            // Log suspicious activity but allow order (could be timing/rounding issue)
            error_log("Discount mismatch for promo {$promo_code}: client provided {$client_discount}, server calculated {$discount}");
        }
    }

    // Calculate totals
    $shipping = 5.00; // Fixed shipping cost for now
    $total_amount = round($subtotal + $shipping - $discount, 2);

    // Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders 
                               (customer_id, order_number, email, subtotal, shipping_amount, discount_amount, 
                                total_amount, status, payment_method, address_id, notes, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->execute([
            $customer_id,
            $order_number,
            $email,
            $subtotal,
            $shipping,
            $discount,
            $total_amount,
            'pending',
            $payment_method,
            $address_id,
            'Promo: ' . ($promo_code ?: 'None')
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert order items
        $itemStmt = $pdo->prepare("INSERT INTO order_items 
                                   (order_id, product_id, product_name, product_sku, quantity, unit_price, total_price, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($cart_items as $item) {
            $product_id = intval($item['product_id']);
            $product_name = isset($item['product_name']) ? trim($item['product_name']) : 'Unknown Product';
            $product_sku = isset($item['sku']) ? trim($item['sku']) : 'N/A';
            $quantity = intval($item['quantity']);
            $unit_price = round(floatval($item['price']), 2);
            $total_price = round($unit_price * $quantity, 2);
            
            $itemStmt->execute([
                $order_id,
                $product_id,
                $product_name,
                $product_sku,
                $quantity,
                $unit_price,
                $total_price
            ]);
        }

        // Handle guest address if provided
        $stored_address_id = $address_id;
        if (!$customer_id && !empty($data['guest_address'])) {
            // Store guest address temporarily (associate with order for reference)
            // In a production system, you might store this as a separate guest_order_addresses table
            $addr = $data['guest_address'];
            $addr_note = json_encode([
                'first_name' => $addr['first_name'],
                'last_name' => $addr['last_name'],
                'address_line_1' => $addr['address_line_1'],
                'address_line_2' => $addr['address_line_2'] ?? '',
                'city' => $addr['city'],
                'state' => $addr['state'],
                'postal_code' => $addr['postal_code'],
                'country' => $addr['country']
            ]);
            
            // Update order notes with guest address
            $updateStmt = $pdo->prepare("UPDATE orders SET notes = CONCAT(notes, '\n\nGuest Address: ', ?) WHERE id = ?");
            $updateStmt->execute([$addr_note, $order_id]);
        }

        // Update promo usage count if promo was applied
        if ($promo_id) {
            $pdo->prepare("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE id = ?")
                ->execute([$promo_id]);
        }

        // Clear cart for logged-in customer
        if ($customer_id) {
            $pdo->prepare("DELETE FROM cart WHERE customer_id = ?")->execute([$customer_id]);
        }

        // Commit transaction
        $pdo->commit();

        // Store order in session for payment verification
        $_SESSION['pending_order'] = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => $total_amount,
            'currency' => 'GBP',
            'promo_code' => $promo_code,
            'discount' => $discount
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order_id,
            'order_number' => $order_number,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method
        ]);

    } catch (Exception $tx_error) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $tx_error->getMessage()]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
