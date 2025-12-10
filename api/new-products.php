<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = $pdo->prepare("SELECT id, name, slug, price FROM products ORDER BY created_at DESC LIMIT 9");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 9) {
        $seed = floor(time() / (6 * 3600));
        mt_srand($seed);
        shuffle($products);
        $products = array_slice($products, 0, 9);
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>