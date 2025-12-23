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

// Load environment variables from .env file
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/email.php';

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

    // Clear customer's cart now that payment is confirmed (if customer exists)
    if (!empty($order['customer_id'])) {
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE customer_id = ?");
            $deleteStmt->execute([$order['customer_id']]);
            error_log('Remita: Cart cleared for customer ' . $order['customer_id'] . ' after payment verified for order ' . $order['id']);
        } catch (Exception $e) {
            error_log('Remita: failed to clear cart for customer ' . $order['customer_id'] . ': ' . $e->getMessage());
        }
    }

    // Log inventory transactions directly
    try {
        error_log("Remita: Starting inventory update for order {$order['id']}");
        
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
            error_log("Remita: WARNING - inventory_transactions table does not exist. Inventory will not be logged. Run /api/setup/init-db.php?key=your-key to create tables.");
        }
        
        $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            error_log("Remita: No order items found for order {$order['id']}");
        } else {
            error_log("Remita: Found " . count($items) . " order items");
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
                
                error_log("Remita: Inventory reduced - Product {$product_id}: {$old_stock} → {$new_stock} (-{$quantity} units for Order #{$order['id']})");
            } else {
                error_log("Remita: Product {$product_id} not found for order {$order['id']}");
            }
        }
        
        error_log("Remita: Inventory update completed for order {$order['id']}");
    } catch (Exception $e) {
        error_log('Remita: ERROR during inventory update for order ' . $order['id'] . ': ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
    }

    // TODO: Create order_items from order
    // TODO: Update inventory
    // TODO: Send confirmation email

    // Best-effort: send confirmation email and record analytics event for payment
    try {
        try {
            send_order_confirmation_email($pdo, $order['id']);
        } catch (Exception $e) {
            error_log('Remita: failed to send confirmation email for order ' . $order['id'] . ': ' . $e->getMessage());
        }

        $aeStmt = $pdo->prepare('INSERT INTO analytics_events (event_name, payload, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())');
        $payload = json_encode([
            'order_id' => (int)$order['id'],
            'order_number' => $order['order_number'],
            'total_amount' => (float)$order['total_amount'],
            'provider' => 'remita',
            'rrr' => $rrr
        ]);
        $userIdForEvent = $order['customer_id'] ?: null;
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $aeStmt->execute(['order_created', $payload, $userIdForEvent, $clientIp]);
    } catch (Exception $e) {
        error_log('Remita: failed to record analytics for order ' . $order['id'] . ': ' . $e->getMessage());
    }

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
