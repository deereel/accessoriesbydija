<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->prepare("UPDATE product_images SET variant_id = (
        SELECT id FROM product_variations 
        WHERE product_variations.product_id = product_images.product_id 
        AND product_variations.tag = product_images.tag 
        LIMIT 1
    ) WHERE variant_id IS NULL AND tag IS NOT NULL AND tag != ''");
    $stmt->execute();
    echo 'Updated ' . $stmt->rowCount() . ' product images with variant_id';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
