<?php
/**
 * Database Update Script: Add variation columns to order_items table
 *
 * This script adds columns for product variations to the order_items table
 * so that order details include material, color, finish, and size information.
 */

require_once 'config/database.php';

try {
    echo "Starting database update for order_items table...\n";

    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $columnsToAdd = [];

    if (!in_array('material_name', $columns)) {
        $columnsToAdd[] = "ADD COLUMN material_name VARCHAR(255) DEFAULT NULL";
    }

    if (!in_array('color', $columns)) {
        $columnsToAdd[] = "ADD COLUMN color VARCHAR(100) DEFAULT NULL";
    }

    if (!in_array('finish', $columns)) {
        $columnsToAdd[] = "ADD COLUMN finish VARCHAR(100) DEFAULT NULL";
    }

    if (!in_array('size', $columns)) {
        $columnsToAdd[] = "ADD COLUMN size VARCHAR(50) DEFAULT NULL";
    }

    if (empty($columnsToAdd)) {
        echo "All required columns already exist in order_items table.\n";
        exit(0);
    }

    // Execute ALTER TABLE statements
    foreach ($columnsToAdd as $alterSql) {
        echo "Executing: ALTER TABLE order_items $alterSql\n";
        $pdo->exec("ALTER TABLE order_items $alterSql");
    }

    echo "Database update completed successfully!\n";
    echo "Added columns: " . implode(', ', array_map(function($col) {
        return preg_replace('/^ADD COLUMN (.*?) .*$/', '$1', $col);
    }, $columnsToAdd)) . "\n";

} catch (PDOException $e) {
    echo "Database update failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>