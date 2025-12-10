<?php
header('Content-Type: application/json');
include '../config/database.php';

try {
    // Get featured products with shuffle every 2 days
    $shuffle_seed = floor(time() / (2 * 24 * 60 * 60)); // Changes every 2 days
    
    $stmt = $pdo->prepare("
        SELECT p.*, pi.image_url 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_featured = 1 AND p.is_active = 1 
        ORDER BY RAND(?) 
        LIMIT 8
    ");
    $stmt->execute([$shuffle_seed]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>