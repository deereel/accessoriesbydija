<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.slug, p.price, pi.image_url
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        ORDER BY p.created_at DESC LIMIT 8
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'products' => $products]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>