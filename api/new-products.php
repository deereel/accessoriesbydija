<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Get products added in last 60 days
    $stmt = $pdo->prepare("
        SELECT id, name, slug, price, category_id 
        FROM products 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
        AND status = 'active'
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $newProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    if (count($newProducts) > 9) {
        // Shuffle every 6 hours using time-based seed
        $seed = floor(time() / (6 * 3600)); // Changes every 6 hours
        mt_srand($seed);
        
        $shuffled = $newProducts;
        shuffle($shuffled);
        $result = array_slice($shuffled, 0, 9);
    } else {
        // If less than 9, get last 9 products regardless of date
        if (count($newProducts) < 9) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, price, category_id 
                FROM products 
                WHERE status = 'active'
                ORDER BY created_at DESC 
                LIMIT 9
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = $newProducts;
        }
    }
    
    echo json_encode(['success' => true, 'products' => $result]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>