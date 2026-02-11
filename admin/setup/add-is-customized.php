<?php
/**
 * Add is_customized Column to Products Table
 * Run this script to add the is_customized column
 */

require_once __DIR__ . '/../../app/config/database.php';

try {
    echo "<h1>Adding is_customized Column</h1>";
    echo "<pre>";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_customized'");
    if ($stmt->fetch()) {
        echo "✓ is_customized column already exists\n";
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE products ADD COLUMN is_customized TINYINT(1) DEFAULT 0 AFTER is_active");
        echo "✓ Added is_customized column to products table\n";
    }
    
    echo "\n========================================\n";
    echo "✓ Migration complete!\n";
    echo "========================================\n";
    echo "</pre>";
    echo "<p><a href='../products.php'>Return to Products</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
