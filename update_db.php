<?php
require_once 'config/database.php';

try {
    // Add category column to products table if it doesn't exist
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) AFTER stone_type");

    echo "Database updated successfully! The 'category' column has been added to the products table.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
