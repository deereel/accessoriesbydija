<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : null;
$payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id is required']);
    exit;
}

$allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$allowed_payment_statuses = ['pending', 'paid', 'failed', 'refunded'];

try {
    // Ensure order exists
    $existsStmt = $pdo->prepare("SELECT id, status, payment_status FROM orders WHERE id = ?");
    $existsStmt->execute([$order_id]);
    $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    $fields = [];
    $params = [];

    if ($status !== null && $status !== '') {
        $status = strtolower($status);
        if (!in_array($status, $allowed_statuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }

        // Validate allowed transitions
        $current_status = $existing['status'];
        $allowed_transitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => [], // No changes allowed after delivery
            'cancelled' => []  // No changes allowed after cancellation
        ];

        if (!in_array($status, $allowed_transitions[$current_status] ?? [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status transition from ' . $current_status . ' to ' . $status]);
            exit;
        }

        $fields[] = "status = ?";
        $params[] = $status;
    }

    if ($payment_status !== null && $payment_status !== '') {
        $payment_status = strtolower($payment_status);
        if (!in_array($payment_status, $allowed_payment_statuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid payment_status']);
            exit;
        }
        $fields[] = "payment_status = ?";
        $params[] = $payment_status;
    }

    if ($notes !== null) {
        $fields[] = "notes = ?";
        $params[] = $notes;
    }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'No changes']);
        exit;
    }

    $sql = "UPDATE orders SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
    $params[] = $order_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Handle inventory adjustments if status changed to cancelled
    if ($status === 'cancelled' && $existing['status'] !== 'cancelled') {
        try {
            error_log("Order Update: Starting inventory restock for cancelled order {$order_id}");

            // Get order items
            $itemsStmt = $pdo->prepare("SELECT oi.product_id, oi.quantity, oi.variation_id, vs.id as size_id FROM order_items oi LEFT JOIN variation_sizes vs ON vs.variation_id = oi.variation_id AND vs.size = oi.size WHERE oi.order_id = ?");
            $itemsStmt->execute([$order_id]);
            $order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get customer name for inventory logs
            $customer_name = "Customer";
            $cstmt = $pdo->prepare("SELECT c.first_name, c.last_name, o.contact_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
            $cstmt->execute([$order_id]);
            $cdata = $cstmt->fetch(PDO::FETCH_ASSOC);
            if ($cdata) {
                if (!empty($cdata['first_name'])) {
                    $customer_name = htmlspecialchars($cdata['first_name'] . ' ' . $cdata['last_name']);
                } elseif (!empty($cdata['contact_name'])) {
                    $customer_name = htmlspecialchars($cdata['contact_name']);
                }
            }

            // Verify inventory tables exist
            $check_tx = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_transactions'");
            $check_logs = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_logs'");
            $has_tx_table = ($check_tx->fetch(PDO::FETCH_NUM)[0] > 0);
            $has_logs_table = ($check_logs->fetch(PDO::FETCH_NUM)[0] > 0);
            if (!$has_tx_table || !$has_logs_table) {
                error_log("Order Update: WARNING - inventory tables do not exist. Inventory will not be logged. Run /api/setup/init-db.php?key=your-key to create tables.");
            }
            $has_tables = $has_tx_table && $has_logs_table;

            foreach ($order_items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $variation_id = $item['variation_id'] ? intval($item['variation_id']) : null;
                $size_id = $item['size_id'] ? intval($item['size_id']) : null;

                // Cascading stock increase (reverse of creation logic)
                $stocks_to_increase = [];
                error_log("Order Update: Product {$product_id} - variation_id: " . ($variation_id ?? 'null') . ", size_id: " . ($size_id ?? 'null'));

                if ($size_id) {
                    // Increase size stock, variation stock, and base stock
                    $stocks_to_increase[] = ['table' => 'variation_sizes', 'id' => $size_id, 'type' => 'size'];
                    if ($variation_id) {
                        $stocks_to_increase[] = ['table' => 'product_variations', 'id' => $variation_id, 'type' => 'variation'];
                    }
                    $stocks_to_increase[] = ['table' => 'products', 'id' => $product_id, 'type' => 'base'];
                } elseif ($variation_id) {
                    // Increase variation stock, base stock, and the size with highest stock for this variation
                    $stocks_to_increase[] = ['table' => 'product_variations', 'id' => $variation_id, 'type' => 'variation'];
                    $stocks_to_increase[] = ['table' => 'products', 'id' => $product_id, 'type' => 'base'];

                    // Find size with highest stock for this variation
                    $sizeStmt = $pdo->prepare("SELECT id FROM variation_sizes WHERE variation_id = ? ORDER BY stock_quantity DESC LIMIT 1");
                    $sizeStmt->execute([$variation_id]);
                    $size_row = $sizeStmt->fetch(PDO::FETCH_ASSOC);
                    if ($size_row) {
                        $stocks_to_increase[] = ['table' => 'variation_sizes', 'id' => intval($size_row['id']), 'type' => 'size'];
                    }
                } else {
                    // Increase base stock, and the variation and size with highest stock
                    $stocks_to_increase[] = ['table' => 'products', 'id' => $product_id, 'type' => 'base'];

                    // Find variation with highest stock for this product
                    $variationStmt = $pdo->prepare("SELECT id FROM product_variations WHERE product_id = ? ORDER BY stock_quantity DESC LIMIT 1");
                    $variationStmt->execute([$product_id]);
                    $variation_row = $variationStmt->fetch(PDO::FETCH_ASSOC);
                    if ($variation_row) {
                        $variation_id_highest = intval($variation_row['id']);
                        $stocks_to_increase[] = ['table' => 'product_variations', 'id' => $variation_id_highest, 'type' => 'variation'];

                        // Find size with highest stock for this variation
                        $sizeStmt = $pdo->prepare("SELECT id FROM variation_sizes WHERE variation_id = ? ORDER BY stock_quantity DESC LIMIT 1");
                        $sizeStmt->execute([$variation_id_highest]);
                        $size_row = $sizeStmt->fetch(PDO::FETCH_ASSOC);
                        if ($size_row) {
                            $stocks_to_increase[] = ['table' => 'variation_sizes', 'id' => intval($size_row['id']), 'type' => 'size'];
                        }
                    }
                }

                error_log("Order Update: Product {$product_id} - stocks_to_increase: " . json_encode($stocks_to_increase));

                // Update all stocks but log only once per item for the most specific stock
                $logged = false;
                foreach ($stocks_to_increase as $stock_info) {
                    $table = $stock_info['table'];
                    $id = $stock_info['id'];
                    $type = $stock_info['type'];

                    $stockStmt = $pdo->prepare("SELECT stock_quantity FROM {$table} WHERE id = ?");
                    $stockStmt->execute([$id]);
                    $stock_row = $stockStmt->fetch(PDO::FETCH_ASSOC);

                    if ($stock_row) {
                        $old_stock = intval($stock_row['stock_quantity']);
                        $new_stock = $old_stock + $quantity;

                        // Update stock
                        try {
                            $updateStmt = $pdo->prepare("UPDATE {$table} SET stock_quantity = ? WHERE id = ?");
                            $update_success = $updateStmt->execute([$new_stock, $id]);
                            $rows_affected = $updateStmt->rowCount();

                            if ($update_success && $rows_affected > 0) {
                                // Log only once per item, for the most specific stock (size > variation > base)
                                if (!$logged && (($type === 'size' && in_array('size', array_column($stocks_to_increase, 'type'))) ||
                                                ($type === 'variation' && !in_array('size', array_column($stocks_to_increase, 'type'))) ||
                                                ($type === 'base' && !in_array('variation', array_column($stocks_to_increase, 'type')) && !in_array('size', array_column($stocks_to_increase, 'type'))))) {
                                    if ($has_tables) {
                                        $notes = "Order cancelled - Restocked for {$customer_name} - Order #{$order_id}";
                                        $logStmt = $pdo->prepare("INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, reference_id, reference_type, previous_stock, new_stock, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                        $logStmt->execute([
                                            $product_id,
                                            'cancellation',
                                            $quantity,
                                            $order_id,
                                            'order',
                                            $old_stock,
                                            $new_stock,
                                            $notes
                                        ]);

                                        $adminLogStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, user_id, action, quantity_change, old_quantity, new_quantity, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                        $admin_user_id = $_SESSION['admin_user_id'] ?? null; // admin user who cancelled
                                        try {
                                            $adminLogStmt->execute([$product_id, $admin_user_id, 'cancellation', $quantity, $old_stock, $new_stock, $notes]);
                                        } catch (Exception $e) {
                                            error_log("Order Update: Failed to log admin inventory for product {$product_id}: " . $e->getMessage());
                                        }
                                    }
                                    $logged = true;
                                }

                                error_log("Order Update: Inventory increased - Product {$product_id} ({$type}): {$old_stock} → {$new_stock} (+{$quantity} units for cancelled Order #{$order_id})");
                            } else {
                                $error_msg = "FAILED to update stock for Product {$product_id} ({$type}) in table {$table} id {$id} - success: {$update_success}, rows: {$rows_affected}, old: {$old_stock}, new: {$new_stock}";
                                error_log("Order Update: " . $error_msg);
                                $_SESSION['inventory_errors'][] = $error_msg;
                            }
                        } catch (Exception $e) {
                            $error_msg = "EXCEPTION updating stock for Product {$product_id} ({$type}) in table {$table} id {$id}: " . $e->getMessage();
                            error_log("Order Update: " . $error_msg);
                            $_SESSION['inventory_errors'][] = $error_msg;
                        }
                    }
                }
            }

            error_log("Order Update: Inventory restock completed for cancelled order {$order_id}");
        } catch (Exception $e) {
            error_log('Order Update: ERROR during inventory restock for cancelled order ' . $order_id . ': ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            // Don't fail the order update if inventory restock fails
        }
    }

    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
