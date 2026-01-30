<?php
/**
 * Check Stock Levels API
 * POST /api/check-stock-levels.php
 * 
 * Returns current stock levels for given product IDs
 * Used by frontend to update product cards in real-time
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_ids']) || !is_array($data['product_ids'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'product_ids array required']);
        exit;
    }
    
    // Sanitize product IDs
    $product_ids = array_map('intval', $data['product_ids']);
    $product_ids = array_filter($product_ids);
    
    if (empty($product_ids)) {
        echo json_encode(['success' => true, 'stocks' => []]);
        exit;
    }
    
    // Build placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    // Fetch stock levels
    $stmt = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($product_ids);
    $stocks = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stocks[$row['id']] = [
            'stock_quantity' => intval($row['stock_quantity'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'stocks' => $stocks
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('check-stock-levels.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error checking stock levels'
    ]);
}
?>
