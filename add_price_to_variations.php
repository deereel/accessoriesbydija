<?php
require_once 'config/database.php';

try {
    $pdo->exec('ALTER TABLE product_variations ADD COLUMN price DECIMAL(10,2) DEFAULT NULL');
    echo 'Added price to product_variations table.';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>