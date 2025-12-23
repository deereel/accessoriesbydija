<?php
/**
 * POST /api/inventory/log_transaction.php
 * Logs an inventory transaction and updates product stock
 * Called by payment handlers after successful payment confirmation
 * 
 * Expected JSON:
 * {
 *   "order_id": 123,
 *   "order_items": [
 *     { "product_id": 1, "quantity": 2 },
 *     { "product_id": 5, "quantity": 1 }
 *   ],
 *   "reference_type": "order" (or "adjustment", "return")
 * }
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['order_id']) || empty($data['order_items']) || !is_array($data['order_items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: order_id, order_items']);
        exit;
    }
    
    $order_id = intval($data['order_id']);
    $order_items = $data['order_items'];
    $reference_type = $data['reference_type'] ?? 'order';
    
    $pdo->beginTransaction();
    
    try {
        foreach ($order_items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            
            if ($quantity <= 0) {
                continue; // Skip invalid quantities
            }
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                error_log("Inventory: Product {$product_id} not found");
                continue;
            }
            
            $old_stock = intval($product['stock_quantity']);
            $new_stock = $old_stock - $quantity; // Reduce stock by order quantity
            
            // Log transaction
            $log_stmt = $pdo->prepare("INSERT INTO inventory_transactions 
                                       (product_id, transaction_type, quantity_change, reference_id, reference_type, previous_stock, new_stock, notes)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $log_stmt->execute([
                $product_id,
                'sale',
                -$quantity,
                $order_id,
                $reference_type,
                $old_stock,
                max(0, $new_stock), // Don't go below 0
                "Order #{$order_id} confirmed"
            ]);
            
            // Log to admin inventory_logs table too
            $admin_log_stmt = $pdo->prepare("INSERT INTO inventory_logs 
                                             (product_id, action, old_quantity, new_quantity)
                                             VALUES (?, ?, ?, ?)");
            $admin_log_stmt->execute([
                $product_id,
                'sale',
                $old_stock,
                max(0, $new_stock)
            ]);
            
            // Update product stock
            $update_stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $update_stmt->execute([max(0, $new_stock), $product_id]);
            
            error_log("Inventory: Product {$product_id} - Reduced by {$quantity} units (Order #{$order_id}). Stock: {$old_stock} → " . max(0, $new_stock));
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Inventory transactions logged successfully',
            'order_id' => $order_id,
            'items_processed' => count($order_items)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Transaction error: ' . $e->getMessage()]);
        error_log("Inventory transaction error: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log("Inventory DB error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
