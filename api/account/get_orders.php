<?php
session_start();
header('Content-Type: application/json');
require_once '../../app/config/database.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Check if orders table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'orders'")->rowCount();
    if ($tableCheck == 0) {
        // Table doesn't exist, return empty orders
        echo json_encode(['success' => true, 'orders' => []]);
        exit;
    }
    
    $sql = "SELECT o.*, 
               COUNT(oi.id) as item_count,
               DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') AS created_at_iso,
               DATE_FORMAT(o.created_at, '%d %b %Y') AS created_at_human
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['customer_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'orders' => $orders ?: []]);
} catch (PDOException $e) {
    error_log('Get orders error: ' . $e->getMessage());
    echo json_encode(['success' => true, 'orders' => []]);
}
?>
