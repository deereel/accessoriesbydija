<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$product_id = $_GET['product_id'] ?? 0;
$material_id = $_GET['material_id'] ?? 0;

if (!$product_id || !$material_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, tag, color, finish, price_adjustment, stock_quantity 
        FROM product_variations 
        WHERE product_id = ? AND material_id = ?
        ORDER BY tag
    ");
    $stmt->execute([$product_id, $material_id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($variations);
} catch (Exception $e) {
    echo json_encode([]);
}
?>