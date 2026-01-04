<?php
require_once 'config/database.php';

try {
    $pdo->exec('ALTER TABLE product_variations ADD COLUMN stock_quantity INT DEFAULT 0');
    echo 'Added stock_quantity to product_variations table.';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

try {
    $pdo->exec('ALTER TABLE variation_sizes ADD COLUMN stock_quantity INT DEFAULT 0');
    echo 'Added stock_quantity to variation_sizes table.';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>