<?php
header('Content-Type: application/json');
include __DIR__ . '/../app/config/database.php';

try {
    // Get featured products with shuffle every 2 days
    $shuffle_seed = floor(time() / (2 * 24 * 60 * 60)); // Changes every 2 days
    
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COALESCE(pi_primary.image_url, pi_first.image_url) as image_url
        FROM products p 
        LEFT JOIN product_images pi_primary ON p.id = pi_primary.product_id AND pi_primary.is_primary = 1
        LEFT JOIN product_images pi_first ON p.id = pi_first.product_id AND pi_first.id = (
            SELECT MIN(id) FROM product_images WHERE product_id = p.id
        )
        WHERE p.is_featured = 1 AND p.is_active = 1 
        ORDER BY RAND(?) 
        LIMIT 8
    ");
    $stmt->execute([$shuffle_seed]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure image URLs are absolute paths (start with /)
    foreach ($products as &$product) {
        if (!empty($product['image_url']) && strpos($product['image_url'], '/') !== 0) {
            $product['image_url'] = '/' . $product['image_url'];
        }
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>