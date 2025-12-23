<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false]); exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // Get 8 most recent products
    $stmt = $pdo->query("
        SELECT id, name, price, CONCAT('/assets/images/products/', LOWER(REPLACE(slug, '-', '_')), '.jpg') as image 
        FROM products 
        WHERE is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'items' => $items]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
