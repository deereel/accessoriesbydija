<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Accept either numeric order_id or order_number parameter
$order_id = $_GET['order_id'] ?? null;
$order_number = $_GET['order_number'] ?? null;
if (!$order_id && !$order_number) {
    echo json_encode(['success' => false, 'message' => 'order_id or order_number required']);
    exit;
}

try {
    if ($order_id && is_numeric($order_id)) {
        $stmt = $pdo->prepare("SELECT *, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at_iso, DATE_FORMAT(created_at, '%d %b %Y') AS created_at_human FROM orders WHERE id = ? AND customer_id = ? LIMIT 1");
        $stmt->execute([$order_id, $_SESSION['customer_id']]);
    } else {
        // lookup by order_number
        $stmt = $pdo->prepare("SELECT *, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at_iso, DATE_FORMAT(created_at, '%d %b %Y') AS created_at_human FROM orders WHERE order_number = ? AND customer_id = ? LIMIT 1");
        $stmt->execute([$order_number, $_SESSION['customer_id']]);
    }
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Fetch items
    $itemsStmt = $pdo->prepare("SELECT oi.*, p.name, p.sku FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
} catch (PDOException $e) {
    error_log('api/account/get_order.php DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
