<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT id, product_id, tag, variant_id FROM product_images WHERE tag IS NOT NULL AND tag != '' LIMIT 10");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Images with tag:\n";
    foreach ($images as $img) {
        echo "ID: {$img['id']}, Product: {$img['product_id']}, Tag: '{$img['tag']}', Variant: {$img['variant_id']}\n";
    }

    $stmt2 = $pdo->query("SELECT id, product_id, tag FROM product_variations WHERE tag IS NOT NULL AND tag != '' LIMIT 10");
    $vars = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "\nVariations with tag:\n";
    foreach ($vars as $v) {
        echo "ID: {$v['id']}, Product: {$v['product_id']}, Tag: '{$v['tag']}'\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
