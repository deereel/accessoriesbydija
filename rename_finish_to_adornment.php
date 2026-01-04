<?php
// Rename 'finish' column to 'adornment' in product_variations and order_items tables
require_once 'config/database.php';

try {
    // Rename in product_variations
    $pdo->exec("ALTER TABLE product_variations CHANGE finish adornment VARCHAR(100) DEFAULT NULL");
    echo "Renamed 'finish' to 'adornment' in product_variations.\n";

    // Rename in order_items
    $pdo->exec("ALTER TABLE order_items CHANGE finish adornment VARCHAR(100) DEFAULT NULL");
    echo "Renamed 'finish' to 'adornment' in order_items.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>