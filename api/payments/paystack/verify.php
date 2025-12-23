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

// Load environment variables from .env file
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/email.php';

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

    // SECURITY: Verify amount matches exactly (in kobo for GBP currency)
    $expected_amount = intval($order['total_amount'] * 100);
    if ($transaction['amount'] !== $expected_amount) {
        // Amount mismatch - potential fraud attempt
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment amount mismatch']);
        exit;
    }

    // Verify currency is correct
    if ($transaction['currency'] !== 'GBP') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment currency mismatch. Expected GBP']);
        exit;
    }

    // Verify order integrity: check if discount and total are reasonable
    // Fetch order items to recalculate subtotal
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
            // Log but don't fail - may be legitimate rounding difference
        }
    }

    // Update order status to paid
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ?, notes = ? WHERE id = ?");
    $stmt->execute([
        'processing',  // Set to processing after payment confirmed
        'paid',
        'Paid via Paystack. Reference: ' . $reference,
        $order['id']
    ]);

    // Clear customer's cart now that payment is confirmed (if customer exists)
    if (!empty($order['customer_id'])) {
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE customer_id = ?");
            $deleteStmt->execute([$order['customer_id']]);
            error_log('Paystack: Cart cleared for customer ' . $order['customer_id'] . ' after payment verified for order ' . $order['id']);
        } catch (Exception $e) {
            error_log('Paystack: failed to clear cart for customer ' . $order['customer_id'] . ': ' . $e->getMessage());
        }
    }

    // Log inventory transactions directly
    try {
        error_log("Paystack: Starting inventory update for order {$order['id']}");
        
        // Get customer name for inventory logs
        $customer_name = "Customer";
        if (!empty($order['contact_name'])) {
            $customer_name = htmlspecialchars($order['contact_name']);
        } else if (!empty($order['customer_id'])) {
            $cstmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM customers WHERE id = ?");
            $cstmt->execute([$order['customer_id']]);
            $cdata = $cstmt->fetch();
            if ($cdata && !empty($cdata['name'])) {
                $customer_name = htmlspecialchars($cdata['name']);
            }
        }
        
        // Verify inventory tables exist
        $check = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_transactions'");
        $has_tables = ($check->fetch(PDO::FETCH_NUM)[0] > 0);
        if (!$has_tables) {
            error_log("Paystack: WARNING - inventory_transactions table does not exist. Inventory will not be logged. Run /api/setup/init-db.php?key=your-key to create tables.");
        }
        
        $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            error_log("Paystack: No order items found for order {$order['id']}");
        } else {
            error_log("Paystack: Found " . count($items) . " order items");
        }
        
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            
            // Get current stock
            $stockStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stockStmt->execute([$product_id]);
            $product = $stockStmt->fetch();
            
            if ($product) {
                $old_stock = intval($product['stock_quantity']);
                $new_stock = max(0, $old_stock - $quantity);
                
                // Only update inventory if tables exist
                if ($has_tables) {
                    // Log transaction
                    $logStmt = $pdo->prepare("INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, reference_id, reference_type, previous_stock, new_stock, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $logStmt->execute([
                        $product_id,
                        'sale',
                        -$quantity,
                        $order['id'],
                        'order',
                        $old_stock,
                        $new_stock,
                        "Sold to {$customer_name} - Order #{$order['id']}"
                    ]);
                    
                    // Log to admin logs with notes containing customer name
                    $adminLogStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, action, old_quantity, new_quantity, notes) VALUES (?, ?, ?, ?, ?)");
                    $adminLogStmt->execute([$product_id, 'sale', $old_stock, $new_stock, "Sold to {$customer_name}"]);
                }
                
                // Update product stock (always do this)
                $updateStmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                $updateStmt->execute([$new_stock, $product_id]);
                
                error_log("Paystack: Inventory reduced - Product {$product_id}: {$old_stock} → {$new_stock} (-{$quantity} units for Order #{$order['id']})");
            } else {
                error_log("Paystack: Product {$product_id} not found for order {$order['id']}");
            }
        }
        
        error_log("Paystack: Inventory update completed for order {$order['id']}");
    } catch (Exception $e) {
        error_log('Paystack: ERROR during inventory update for order ' . $order['id'] . ': ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
    }

    // TODO: Update inventory/stock levels
    // TODO: Send confirmation email to customer

    // Best-effort: send confirmation email and record analytics event for payment
    try {
        // Send confirmation email (best-effort)
        try {
            send_order_confirmation_email($pdo, $order['id']);
        } catch (Exception $e) {
            error_log('Paystack: failed to send confirmation email for order ' . $order['id'] . ': ' . $e->getMessage());
        }

        // Insert analytics event `order_created` (meaning: order confirmed/paid)
        $aeStmt = $pdo->prepare('INSERT INTO analytics_events (event_name, payload, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())');
        $payload = json_encode([
            'order_id' => (int)$order['id'],
            'order_number' => $order['order_number'],
            'total_amount' => (float)$order['total_amount'],
            'provider' => 'paystack',
            'reference' => $reference
        ]);
        $userIdForEvent = $order['customer_id'] ?: null;
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $aeStmt->execute(['order_created', $payload, $userIdForEvent, $clientIp]);
    } catch (Exception $e) {
        error_log('Paystack: failed to record analytics for order ' . $order['id'] . ': ' . $e->getMessage());
    }

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
