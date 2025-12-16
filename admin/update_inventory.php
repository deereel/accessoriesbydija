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

$action = $_POST['action'] ?? '';

if ($action === 'update_stock') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : null;

    if ($product_id <= 0 || $stock_quantity === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product ID and stock quantity are required.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get old quantity
        $stmt_old = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
        $stmt_old->execute([$product_id]);
        $old_quantity = $stmt_old->fetchColumn();

        if ($old_quantity === false) {
            throw new Exception("Product not found.");
        }

        // Update stock
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$stock_quantity, $product_id]);

        // Log the change
        $log_stmt = $pdo->prepare(
            "INSERT INTO inventory_logs (product_id, user_id, action, quantity_change, old_quantity, new_quantity, reason) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $admin_user_id = $_SESSION['admin_user_id'] ?? null;
        $reason = 'Manual stock update from inventory page.';
        $quantity_change = $stock_quantity - (int)$old_quantity;

        $log_stmt->execute([$product_id, $admin_user_id, 'manual_update', $quantity_change, (int)$old_quantity, $stock_quantity, $reason]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Stock updated successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>