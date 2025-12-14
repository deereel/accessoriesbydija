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

require_once __DIR__ . '/../../../config/database.php';

// TODO: Get webhook signing secret from environment
$STRIPE_WEBHOOK_SECRET = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_your_webhook_secret_here';

// Get raw request body
$raw_body = file_get_contents('php://input');
$event = null;

try {
    $event = json_decode($raw_body);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
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

        // TODO: Create order_items from session
        // TODO: Update inventory
        // TODO: Send confirmation email

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
