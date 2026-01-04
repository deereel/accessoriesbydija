<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->prepare("UPDATE order_items SET variation_id = (
        SELECT id FROM product_variations
        WHERE product_variations.product_id = order_items.product_id
        AND TRIM(LOWER(product_variations.tag)) = TRIM(LOWER(order_items.variation_tag))
        LIMIT 1
    ) WHERE variation_id IS NULL AND variation_tag IS NOT NULL AND variation_tag != ''");
    $stmt->execute();
    echo 'Updated ' . $stmt->rowCount() . ' order items with variation_id';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
