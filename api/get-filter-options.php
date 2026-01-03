<?php
require_once '../config/database.php';

$material = $_GET['material'] ?? '';
$variant = $_GET['variant'] ?? '';

$variants = [];
$sizes = [];

if (!empty($material)) {
    $stmt = $pdo->prepare("SELECT DISTINCT pv.tag FROM product_variants pv JOIN product_materials pm ON pv.product_id = pm.product_id JOIN materials m ON pm.material_id = m.id WHERE LOWER(m.name) = ? ORDER BY pv.tag ASC");
    $stmt->execute([strtolower($material)]);
    $variants = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (!empty($variant)) {
    $stmt = $pdo->prepare("SELECT DISTINCT p.size FROM products p JOIN product_variants pv ON p.id = pv.product_id WHERE LOWER(pv.tag) = ? AND p.size IS NOT NULL AND p.size != '' ORDER BY p.size ASC");
    $stmt->execute([strtolower($variant)]);
    $sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

echo json_encode(['variants' => $variants, 'sizes' => $sizes]);
?>