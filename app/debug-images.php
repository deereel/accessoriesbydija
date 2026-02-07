<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, 
               COALESCE(pi_primary.image_url, pi_first.image_url) as image_url
        FROM products p 
        LEFT JOIN product_images pi_primary ON p.id = pi_primary.product_id AND pi_primary.is_primary = 1
        LEFT JOIN product_images pi_first ON p.id = pi_first.product_id AND pi_first.id = (
            SELECT MIN(id) FROM product_images WHERE product_id = p.id
        )
        WHERE p.is_active = 1 
        LIMIT 3
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info = ['products' => []];
    
    foreach ($products as $product) {
        $original = $product['image_url'];
        $absolute = $original && strpos($original, '/') !== 0 ? '/' . $original : $original;
        
        $debug_info['products'][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'original_path' => $original,
            'absolute_path' => $absolute
        ];
    }
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>