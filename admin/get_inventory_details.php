<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../config/database.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

try {
    // Get variations
    $stmt = $pdo->prepare("SELECT tag, stock_quantity FROM product_variations WHERE product_id = ? AND stock_quantity > 0 ORDER BY tag");
    $stmt->execute([$product_id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sizes
    $stmt2 = $pdo->prepare("SELECT vs.size, vs.stock_quantity FROM variation_sizes vs JOIN product_variations pv ON vs.variation_id = pv.id WHERE pv.product_id = ? AND vs.stock_quantity > 0 ORDER BY vs.size");
    $stmt2->execute([$product_id]);
    $sizes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'variations' => $variations,
        'sizes' => $sizes
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>