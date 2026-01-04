<?php
require_once 'config/database.php';

try {
    $pdo->exec('ALTER TABLE order_items ADD COLUMN variation_id INT NULL;');
    echo 'Column variation_id added successfully to order_items table.';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
