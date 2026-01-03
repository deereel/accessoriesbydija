<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$variation_id = $_GET['variation_id'] ?? 0;

if (!$variation_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, size, stock_quantity, price_adjustment 
        FROM variation_sizes 
        WHERE variation_id = ?
        ORDER BY 
            CASE 
                WHEN size REGEXP '^[0-9]+$' THEN CAST(size AS UNSIGNED)
                ELSE 999 
            END,
            size
    ");
    $stmt->execute([$variation_id]);
    $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($sizes);
} catch (Exception $e) {
    echo json_encode([]);
}
?>