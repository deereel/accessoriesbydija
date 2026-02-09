<?php
/**
 * Sale Price Feature Migration
 * Run this file to add sale price columns to the products table
 * 
 * Usage: 
 * - Via browser: Navigate to http://localhost/accessoriesbydija/app/memory/sale_price_migration.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "Checking for existing sale price columns...\n";
    
    // Check which columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n";
    
    // Add missing columns
    if (!in_array('is_on_sale', $existingColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN is_on_sale TINYINT(1) DEFAULT 0 AFTER price");
        echo "Added is_on_sale column\n";
    } else {
        echo "is_on_sale column already exists\n";
    }
    
    if (!in_array('sale_price', $existingColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL AFTER is_on_sale");
        echo "Added sale_price column\n";
    } else {
        echo "sale_price column already exists\n";
    }
    
    if (!in_array('sale_percentage', $existingColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN sale_percentage INT DEFAULT NULL AFTER sale_price");
        echo "Added sale_percentage column\n";
    } else {
        echo "sale_percentage column already exists\n";
    }
    
    if (!in_array('sale_end_date', $existingColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN sale_end_date DATETIME DEFAULT NULL AFTER sale_percentage");
        echo "Added sale_end_date column\n";
    } else {
        echo "sale_end_date column already exists\n";
    }
    
    // Create index for faster queries (handle case where it may already exist)
    try {
        $pdo->exec("CREATE INDEX idx_products_sale ON products(is_on_sale, sale_end_date)");
        echo "Created index\n";
    } catch (PDOException $e) {
        // Index might already exist
        echo "Index info: " . $e->getMessage() . "\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
