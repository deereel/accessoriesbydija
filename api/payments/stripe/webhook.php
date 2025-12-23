<?php
/**
 * Stripe Webhook Handler
 * POST /api/payments/stripe/webhook.php
 * 
 * Handles webhooks from Stripe for payment confirmations
 * 
 * WEBHOOK EVENTS:
 * - checkout.session.completed: Customer completed checkout (payment processing)
 * - payment_intent.succeeded: Payment intent succeeded (payment successful)
 * 
 * SECURITY:
 * - Always verify webhook signature using STRIPE_WEBHOOK_SECRET
 * - Check event status before updating orders
 * - Verify amount matches expected value
 * - Idempotent: Handle duplicate webhooks gracefully
 */

header('Content-Type: application/json');

// Load environment variables from .env file
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/email.php';

// TODO: Get webhook signing secret from environment
$STRIPE_WEBHOOK_SECRET = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_your_webhook_secret_here';

// Get raw request body
$raw_body = file_get_contents('php://input');
$event = null;

// Log the request method and content type for debugging
error_log("Webhook request - Method: " . $_SERVER['REQUEST_METHOD'] . ", Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'none'));

if (empty($raw_body)) {
    http_response_code(400);
    error_log("Webhook error: Empty request body");
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

// Decode JSON
$event = json_decode($raw_body);

if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON or malformed event']);
    error_log("Webhook JSON decode failed. Raw body: " . substr($raw_body, 0, 500));
    exit;
}

// Validate event has required type field
if (empty($event->type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Event missing type field']);
    error_log("Webhook event missing type field. Event: " . json_encode($event));
    exit;
}

// Verify webhook signature
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// TODO: Implement signature verification
// $expected_sig = hash_hmac('sha256', $raw_body, $STRIPE_WEBHOOK_SECRET);
// Note: Stripe uses a more complex signature verification - see:
// https://stripe.com/docs/webhooks/signatures

// For now, basic validation
if (empty($sig_header)) {
    // In production, reject unsigned webhooks
    // For testing, you may skip this
}

// Handle different event types
if (!isset($event->type)) {
    http_response_code(400);
    echo json_encode(['error' => 'No event type specified']);
    exit;
}

switch ($event->type) {
    case 'checkout.session.completed':
        handleCheckoutSessionCompleted($event, $pdo);
        break;
    
    case 'payment_intent.succeeded':
        handlePaymentIntentSucceeded($event, $pdo);
        break;
    
    case 'charge.failed':
        handleChargeFailed($event, $pdo);
        break;
    
    default:
        // Ignore other event types
        error_log("Unhandled webhook event type: " . $event->type);
        break;
}

echo json_encode(['success' => true]);

/**
 * Handle checkout.session.completed event
 */
function handleCheckoutSessionCompleted($event, $pdo) {
    $session = $event->data->object;

    if ($session->payment_status !== 'paid') {
        return; // Payment not completed
    }

    try {
        // Find order by metadata
        $order_id = $session->metadata->order_id ?? null;
        
        if (!$order_id) {
            // Fallback: search by order_number in metadata
            $order_number = $session->metadata->order_number ?? null;
            if ($order_number) {
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
                $stmt->execute([$order_number]);
                $order = $stmt->fetch();
                $order_id = $order['id'] ?? null;
            }
        }

        if (!$order_id) {
            return; // Cannot find order
        }

        // Fetch order to verify amount
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order) {
            return; // Order not found
        }

        // SECURITY: Verify amount matches (in pence for GBP)
        $expected_amount = intval($order['total_amount'] * 100);
        if ($session->amount_total !== $expected_amount) {
            // Amount mismatch - potential fraud
            error_log('Stripe amount mismatch for order_id=' . $order_id . ': expected=' . $expected_amount . ', received=' . $session->amount_total);
            return;
        }

        // Verify order integrity: check if discount and total are reasonable
        $itemsStmt = $pdo->prepare("SELECT SUM(quantity * unit_price) as item_subtotal FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$order_id]);
        $itemResult = $itemsStmt->fetch();
        $item_subtotal = $itemResult ? floatval($itemResult['item_subtotal'] ?? 0) : 0;
        
        // If order items exist, verify totals are consistent
        if ($item_subtotal > 0) {
            $calculated_total = $item_subtotal + floatval($order['shipping_amount']) - floatval($order['discount_amount']);
            $calculated_total = round($calculated_total, 2);
            
            // Allow small tolerance for rounding
            if (abs($calculated_total - floatval($order['total_amount'])) > 0.01) {
                error_log("Order amount mismatch for order_id={$order_id}: calculated={$calculated_total}, recorded={$order['total_amount']}");
            }
        }

        // Check if already processed (idempotent)
        if ($order['status'] === 'paid' || $order['status'] === 'processing') {
            return; // Already processed
        }

        // Update order status to paid
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ?, notes = ? WHERE id = ?");
        $stmt->execute([
            'processing',
            'paid',
            'Paid via Stripe. Session: ' . $session->id,
            $order_id
        ]);

        // Clear customer's cart now that payment is confirmed (if customer exists)
        if (!empty($order['customer_id'])) {
            try {
                $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE customer_id = ?");
                $deleteStmt->execute([$order['customer_id']]);
                error_log('Stripe: Cart cleared for customer ' . $order['customer_id'] . ' after payment confirmed for order ' . $order_id);
            } catch (Exception $e) {
                error_log('Stripe: failed to clear cart for customer ' . $order['customer_id'] . ': ' . $e->getMessage());
            }
        }

        // Log inventory transactions directly
        try {
            error_log("Stripe: Starting inventory update for order {$order_id}");
            
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
                error_log("Stripe: WARNING - inventory_transactions table does not exist. Inventory will not be logged. Run /api/setup/init-db.php?key=your-key to create tables.");
            }
            
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$order_id]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($items)) {
                error_log("Stripe: No order items found for order {$order_id}");
            } else {
                error_log("Stripe: Found " . count($items) . " order items");
            }
            
            $inventory_updated_count = 0;
            foreach ($items as $item) {
                try {
                    $product_id = intval($item['product_id']);
                    $quantity = intval($item['quantity']);
                    
                    if ($quantity <= 0) {
                        error_log("Stripe: Skipping item with invalid quantity: {$quantity}");
                        continue;
                    }
                    
                    // Get current stock
                    $stockStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                    $stockStmt->execute([$product_id]);
                    $product = $stockStmt->fetch();
                    
                    if (!$product) {
                        error_log("Stripe: Product {$product_id} not found for order {$order_id}");
                        continue;
                    }
                    
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
                            $order_id,
                            'order',
                            $old_stock,
                            $new_stock,
                            "Sold to {$customer_name} - Order #{$order_id}"
                        ]);
                        
                        // Log to admin logs with notes containing customer name
                        $adminLogStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, action, old_quantity, new_quantity, notes) VALUES (?, ?, ?, ?, ?)");
                        $adminLogStmt->execute([$product_id, 'sale', $old_stock, $new_stock, "Sold to {$customer_name}"]);
                    }
                    
                    // Update product stock (ALWAYS do this, even if logging tables don't exist)
                    $updateStmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                    $updateResult = $updateStmt->execute([$new_stock, $product_id]);
                    
                    if ($updateResult) {
                        $inventory_updated_count++;
                        error_log("Stripe: Stock updated - Product {$product_id}: {$old_stock} → {$new_stock} (-{$quantity} units for Order #{$order_id})");
                    } else {
                        error_log("Stripe: FAILED to update stock for Product {$product_id}");
                    }
                    
                } catch (Exception $itemEx) {
                    error_log('Stripe: ERROR processing inventory for item ' . ($item['product_id'] ?? 'unknown') . ': ' . $itemEx->getMessage());
                    continue; // Continue with next item even if this one fails
                }
            }
            
            error_log("Stripe: Inventory update completed for order {$order_id}. Updated {$inventory_updated_count} product(s)");
        } catch (Exception $e) {
            error_log('Stripe: ERROR during inventory update for order ' . $order_id . ': ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
        }

        // TODO: Create order_items from session
        // TODO: Update inventory
        // TODO: Send confirmation email

        // Best-effort: send confirmation email and record analytics event
        try {
            try {
                send_order_confirmation_email($pdo, $order_id);
            } catch (Exception $e) {
                error_log('Stripe: failed to send confirmation email for order ' . $order_id . ': ' . $e->getMessage());
            }

            $aeStmt = $pdo->prepare('INSERT INTO analytics_events (event_name, payload, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())');
            $payload = json_encode([
                'order_id' => (int)$order_id,
                'order_number' => $order['order_number'] ?? null,
                'total_amount' => (float)$order['total_amount'] ?? null,
                'provider' => 'stripe',
                'session_id' => $session->id
            ]);
            $userIdForEvent = $order['customer_id'] ?? null;
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
            $aeStmt->execute(['order_created', $payload, $userIdForEvent, $clientIp]);
        } catch (Exception $e) {
            error_log('Stripe: failed to record analytics for order ' . $order_id . ': ' . $e->getMessage());
        }

    } catch (PDOException $e) {
        // Log error but don't fail webhook
        error_log('Stripe webhook error: ' . $e->getMessage());
    }
}

/**
 * Handle payment_intent.succeeded event
 */
function handlePaymentIntentSucceeded($event, $pdo) {
    $payment_intent = $event->data->object;

    // This is similar to checkout.session.completed
    // Use metadata to find and update order
    try {
        $order_id = $payment_intent->metadata->order_id ?? null;
        
        if (!$order_id) {
            $order_number = $payment_intent->metadata->order_number ?? null;
            if ($order_number) {
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
                $stmt->execute([$order_number]);
                $order = $stmt->fetch();
                $order_id = $order['id'] ?? null;
            }
        }

        if ($order_id) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ? AND status = ?");
            $stmt->execute([
                'paid',
                'Paid via Stripe. Intent: ' . $payment_intent->id,
                $order_id,
                'pending'
            ]);

            // Clear customer's cart now that payment is confirmed (if customer exists)
            $stmt2 = $pdo->prepare("SELECT customer_id FROM orders WHERE id = ?");
            $stmt2->execute([$order_id]);
            $o = $stmt2->fetch(PDO::FETCH_ASSOC);
            if (!empty($o['customer_id'])) {
                try {
                    $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE customer_id = ?");
                    $deleteStmt->execute([$o['customer_id']]);
                    error_log('Stripe: Cart cleared for customer ' . $o['customer_id'] . ' after payment_intent succeeded for order ' . $order_id);
                } catch (Exception $e) {
                    error_log('Stripe: failed to clear cart for customer ' . $o['customer_id'] . ': ' . $e->getMessage());
                }
            }

            // Log inventory transactions
            try {
                $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$order_id]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($items)) {
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => 'http://' . $_SERVER['HTTP_HOST'] . '/api/inventory/log_transaction.php',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                        CURLOPT_POSTFIELDS => json_encode([
                            'order_id' => $order_id,
                            'order_items' => $items,
                            'reference_type' => 'order'
                        ]),
                        CURLOPT_TIMEOUT => 10
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                }
            } catch (Exception $e) {
                error_log('Stripe: failed to log inventory for order ' . $order_id . ': ' . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log('Stripe webhook error: ' . $e->getMessage());
    }
}

/**
 * Handle charge.failed event
 */
function handleChargeFailed($event, $pdo) {
    $charge = $event->data->object;

    try {
        // Find and update order to failed
        $order_id = $charge->metadata->order_id ?? null;
        
        if ($order_id) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([
                'failed',
                'Payment failed via Stripe. Charge: ' . $charge->id,
                $order_id
            ]);
        }
    } catch (PDOException $e) {
        error_log('Stripe webhook error: ' . $e->getMessage());
    }
}
?>
