 <?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/shipping-calculator.php';
require_once __DIR__ . '/../../includes/email.php';

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

    // Validate CSRF token
    if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

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
    $country = null;
    $guest_addr = null;

    // Fetch cart items and determine country
    if ($customer_id) {
        // Logged-in customer: fetch from database
        $stmt = $pdo->prepare("SELECT c.id, c.product_id, c.quantity, c.material_id, c.variation_id, c.size_id, COALESCE(c.selected_price, pv.price_adjustment, p.price) as price, p.name, p.sku,
                               m.name as material_name, pv.color, pv.adornment, vs.size, pv.tag as variation_tag
                               FROM cart c
                               JOIN products p ON c.product_id = p.id
                               LEFT JOIN materials m ON m.id = c.material_id
                               LEFT JOIN product_variations pv ON pv.id = c.variation_id
                               LEFT JOIN variation_sizes vs ON vs.id = c.size_id
                               WHERE c.customer_id = ?");
        $stmt->execute([$customer_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get country from address
        if ($address_id) {
            $addrStmt = $pdo->prepare("SELECT country FROM customer_addresses WHERE id = ? AND customer_id = ?");
            $addrStmt->execute([$address_id, $customer_id]);
            $addr = $addrStmt->fetch(PDO::FETCH_ASSOC);
            if (!$addr) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid address for this customer']);
                exit;
            }
            $country = $addr['country'] ?? 'United Kingdom';
        } else {
            // Default to UK if no address specified
            $country = 'United Kingdom';
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
        $country = $guest_addr['country'];
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

    // Validate stock availability for all items before proceeding
    foreach ($cart_items as $item) {
        $product_id = intval($item['product_id']);
        $qty = intval($item['quantity']);
        $variation_id = $item['variation_id'] ?? null;
        $size_id = $item['size_id'] ?? null;

        $stmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product not found: ' . htmlspecialchars($item['product_name'] ?? 'Unknown')]);
            exit;
        }

        // Check variation stock if applicable
        if ($variation_id) {
            $vstmt = $pdo->prepare("SELECT stock_quantity FROM product_variations WHERE id = ?");
            $vstmt->execute([$variation_id]);
            $vstock = $vstmt->fetchColumn();
            if ($vstock <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $product['name'] . ' variation is out of stock']);
                exit;
            }
            if ($vstock < $qty) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Only ' . $vstock . ' units of ' . $product['name'] . ' variation available in stock']);
                exit;
            }
        } elseif ($size_id) {
            // Check size stock if no variation but has size
            $sstmt = $pdo->prepare("SELECT stock_quantity FROM variation_sizes WHERE id = ?");
            $sstmt->execute([$size_id]);
            $sstock = $sstmt->fetchColumn();
            if ($sstock <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $product['name'] . ' size is out of stock']);
                exit;
            }
            if ($sstock < $qty) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Only ' . $sstock . ' units of ' . $product['name'] . ' size available in stock']);
                exit;
            }
        } else {
            // Check base product stock
            if ($product['stock_quantity'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $product['name'] . ' is out of stock']);
                exit;
            }
            if ($product['stock_quantity'] < $qty) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Only ' . $product['stock_quantity'] . ' units of ' . $product['name'] . ' available in stock']);
                exit;
            }
        }
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

    // Calculate shipping based on country and total weight
    $total_weight = calculateTotalWeight($cart_items, $pdo);
    
    // Use shipping cost from request if provided, otherwise calculate it
    if (isset($data['shipping_cost'])) {
        $shipping = floatval($data['shipping_cost']);
    } else {
        $shipping = calculateShippingFee($country, $total_weight, $subtotal, $customer_id, $pdo);
        
        // If country not recognized, use default for now (should be handled by client)
        if ($shipping === null) {
            $shipping = 5.00; // Default shipping if location not found
        }
    }
    
    $total_amount = round($subtotal + $shipping - $discount, 2);

    // Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders
                               (customer_id, order_number, email, contact_name, contact_phone, subtotal, shipping_amount, discount_amount,
                                total_amount, status, payment_method, address_id, notes, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $customer_id,
            $order_number,
            $email,
            $data['contact_name'] ?? null,
            $data['contact_phone'] ?? null,
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
                                   (order_id, product_id, product_name, product_sku, quantity, unit_price, total_price, material_name, color, adornment, size, variation_id, variation_tag, created_at)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($cart_items as $item) {
            $product_id = intval($item['product_id']);
            
            // Always fetch the canonical product name from the products table to ensure it's correct
            $p_stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $p_stmt->execute([$product_id]);
            $product_data = $p_stmt->fetch(PDO::FETCH_ASSOC);

            // Use the fetched name, or fallback to the cart item name (which can be 'name' or 'product_name'), or finally 'Unknown'
            $product_name = $product_data['name'] ?? ($item['name'] ?? ($item['product_name'] ?? 'Unknown Product'));
            $product_name = trim($product_name);

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
                $total_price,
                $item['material_name'] ?? null,
                $item['color'] ?? null,
                $item['adornment'] ?? null,
                $item['size'] ?? null,
                $item['variation_id'] ?? null,
                $item['variation_tag'] ?? null
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

        // Update inventory/stock levels after order creation
        try {
            error_log("Order Create: Starting inventory update for order {$order_id}");

            // Get customer name for inventory logs
            $customer_name = "Customer";
            if (!empty($data['contact_name'])) {
                $customer_name = htmlspecialchars($data['contact_name']);
            } else if ($customer_id) {
                $cstmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM customers WHERE id = ?");
                $cstmt->execute([$customer_id]);
                $cdata = $cstmt->fetch(PDO::FETCH_ASSOC);
                if ($cdata && !empty($cdata['name'])) {
                    $customer_name = htmlspecialchars($cdata['name']);
                }
            }

            // Verify inventory tables exist
            $check = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_transactions'");
            $has_tables = ($check->fetch(PDO::FETCH_NUM)[0] > 0);
            if (!$has_tables) {
                error_log("Order Create: WARNING - inventory_transactions table does not exist. Inventory will not be logged. Run /api/setup/init-db.php?key=your-key to create tables.");
            }

            foreach ($cart_items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $variation_id = $item['variation_id'] ?? null;
                $size_id = $item['size_id'] ?? null;

                // Cascading stock reduction
                $stocks_to_reduce = [];
                error_log("Order Create: Product {$product_id} - variation_id: " . ($variation_id ?? 'null') . ", size_id: " . ($size_id ?? 'null'));

                if ($size_id) {
                    // Reduce size stock, variation stock, and base stock
                    $stocks_to_reduce[] = ['table' => 'variation_sizes', 'id' => $size_id, 'type' => 'size'];
                    if ($variation_id) {
                        $stocks_to_reduce[] = ['table' => 'product_variations', 'id' => $variation_id, 'type' => 'variation'];
                    }
                    $stocks_to_reduce[] = ['table' => 'products', 'id' => $product_id, 'type' => 'base'];
                } elseif ($variation_id) {
                    // Reduce variation stock, base stock, and the size with highest stock for this variation
                    $stocks_to_reduce[] = ['table' => 'product_variations', 'id' => $variation_id, 'type' => 'variation'];
                    $stocks_to_reduce[] = ['table' => 'products', 'id' => $product_id, 'type' => 'base'];

                    // Find size with highest stock for this variation
                    $sizeStmt = $pdo->prepare("SELECT id FROM variation_sizes WHERE variation_id = ? ORDER BY stock_quantity DESC LIMIT 1");
                    $sizeStmt->execute([$variation_id]);
                    $size_row = $sizeStmt->fetch(PDO::FETCH_ASSOC);
                    if ($size_row) {
                        $stocks_to_reduce[] = ['table' => 'variation_sizes', 'id' => intval($size_row['id']), 'type' => 'size'];
                    }
                } else {
                    // Reduce base stock, and the variation and size with highest stock
                    $stocks_to_reduce[] = ['table' => 'products', 'id' => $product_id, 'type' => 'base'];

                    // Find variation with highest stock for this product
                    $variationStmt = $pdo->prepare("SELECT id FROM product_variations WHERE product_id = ? ORDER BY stock_quantity DESC LIMIT 1");
                    $variationStmt->execute([$product_id]);
                    $variation_row = $variationStmt->fetch(PDO::FETCH_ASSOC);
                    if ($variation_row) {
                        $variation_id_highest = intval($variation_row['id']);
                        $stocks_to_reduce[] = ['table' => 'product_variations', 'id' => $variation_id_highest, 'type' => 'variation'];

                        // Find size with highest stock for this variation
                        $sizeStmt = $pdo->prepare("SELECT id FROM variation_sizes WHERE variation_id = ? ORDER BY stock_quantity DESC LIMIT 1");
                        $sizeStmt->execute([$variation_id_highest]);
                        $size_row = $sizeStmt->fetch(PDO::FETCH_ASSOC);
                        if ($size_row) {
                            $stocks_to_reduce[] = ['table' => 'variation_sizes', 'id' => intval($size_row['id']), 'type' => 'size'];
                        }
                    }
                }

                error_log("Order Create: Product {$product_id} - stocks_to_reduce: " . json_encode($stocks_to_reduce));

                // Update all stocks but log only once per item for the most specific stock
                $logged = false;
                foreach ($stocks_to_reduce as $stock_info) {
                    $table = $stock_info['table'];
                    $id = $stock_info['id'];
                    $type = $stock_info['type'];
   
                    $stockStmt = $pdo->prepare("SELECT stock_quantity FROM {$table} WHERE id = ?");
                    $stockStmt->execute([$id]);
                    $stock_row = $stockStmt->fetch(PDO::FETCH_ASSOC);
   
                    if ($stock_row) {
                        $old_stock = intval($stock_row['stock_quantity']);
                        $new_stock = max(0, $old_stock - $quantity);
   
                        // Update stock
                        try {
                            $updateStmt = $pdo->prepare("UPDATE {$table} SET stock_quantity = ? WHERE id = ?");
                            $update_success = $updateStmt->execute([$new_stock, $id]);
                            $rows_affected = $updateStmt->rowCount();
   
                            if ($update_success && $rows_affected > 0) {
                                // Log only once per item, for the most specific stock (size > variation > base)
                                if (!$logged && (($type === 'size' && in_array('size', array_column($stocks_to_reduce, 'type'))) ||
                                                ($type === 'variation' && !in_array('size', array_column($stocks_to_reduce, 'type'))) ||
                                                ($type === 'base' && !in_array('variation', array_column($stocks_to_reduce, 'type')) && !in_array('size', array_column($stocks_to_reduce, 'type'))))) {
                                    if ($has_tables) {
                                        $notes = "Sold to {$customer_name} - Order #{$order_id}";
                                        $logStmt = $pdo->prepare("INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, reference_id, reference_type, previous_stock, new_stock, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                        $logStmt->execute([
                                            $product_id,
                                            'sale',
                                            -$quantity,
                                            $order_id,
                                            'order',
                                            $old_stock,
                                            $new_stock,
                                            $notes
                                        ]);
   
                                        $adminLogStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, user_id, action, quantity_change, old_quantity, new_quantity, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                        $admin_user_id = null; // system action; no admin user
                                        $adminLogStmt->execute([$product_id, $admin_user_id, 'sale', -$quantity, $old_stock, $new_stock, $notes]);
                                    }
                                    $logged = true;
                                }
   
                                error_log("Order Create: Inventory reduced - Product {$product_id} ({$type}): {$old_stock} → {$new_stock} (-{$quantity} units for Order #{$order_id})");
                            } else {
                                $error_msg = "FAILED to update stock for Product {$product_id} ({$type}) in table {$table} id {$id} - success: {$update_success}, rows: {$rows_affected}, old: {$old_stock}, new: {$new_stock}";
                                error_log("Order Create: " . $error_msg);
                                $_SESSION['inventory_errors'][] = $error_msg;
                            }
                        } catch (Exception $e) {
                            $error_msg = "EXCEPTION updating stock for Product {$product_id} ({$type}) in table {$table} id {$id}: " . $e->getMessage();
                            error_log("Order Create: " . $error_msg);
                            $_SESSION['inventory_errors'][] = $error_msg;
                        }
                    }
                }
            }

            error_log("Order Create: Inventory update completed for order {$order_id}");
        } catch (Exception $e) {
            error_log('Order Create: ERROR during inventory update for order ' . $order_id . ': ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
        }

        // NOTE: Do NOT clear the customer's cart here. The cart should be cleared
        // only after payment confirmation from the gateway (webhook/verify handler)
        // to avoid losing items when payment fails or is abandoned.

        // Commit transaction
        $pdo->commit();

        // Send order confirmation email (best-effort)
        try {
            send_order_confirmation_email($pdo, $order_id);
        } catch (Exception $e) {
            error_log('Failed to send order confirmation email for order ' . $order_id . ': ' . $e->getMessage());
        }

        // Send admin notification email
        try {
            send_admin_order_notification($pdo, $order_id);
        } catch (Exception $e) {
            error_log('Failed to send admin notification for order ' . $order_id . ': ' . $e->getMessage());
        }

        // NOTE: server-side analytics for order creation is recorded when payment
        // is confirmed (payment provider webhook/verify handlers). Client-side
        // order_created events are still emitted from the confirmation page.

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
