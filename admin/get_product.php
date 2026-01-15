<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$product_id = $_GET['id'] ?? '';
if (!$product_id) {
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Get product variations
    $stmt = $pdo->prepare("SELECT pv.*, m.name as material_name FROM product_variations pv LEFT JOIN materials m ON pv.material_id = m.id WHERE pv.product_id = ?");
    $stmt->execute([$product_id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sizes for each variation
    foreach ($variations as &$variation) {
        $stmt = $pdo->prepare("SELECT * FROM variation_sizes WHERE variation_id = ?");
        $stmt->execute([$variation['id']]);
        $variation['sizes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get product images
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $product['variations'] = $variations;
    $product['images'] = $images;

    // Map gender to short form
    $gender_map = ['Unisex' => 'U', 'Male' => 'M', 'Female' => 'F'];
    $product['gender'] = $gender_map[$product['gender']] ?? $product['gender'];

    header('Content-Type: application/json');
    echo json_encode($product);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>