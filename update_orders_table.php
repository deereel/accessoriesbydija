<?php
/**
 * Database Update Script: Add missing columns to orders table
 *
 * This script adds the missing columns (email, discount_amount, address_id)
 * to the existing orders table to fix the SQL error during order creation.
 */

require_once 'config/database.php';

try {
    echo "Starting database update for orders table...\n";

    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $columnsToAdd = [];

    if (!in_array('email', $columns)) {
        $columnsToAdd[] = "ADD COLUMN email VARCHAR(255) AFTER shipping_method";
    }

    if (!in_array('contact_name', $columns)) {
        $columnsToAdd[] = "ADD COLUMN contact_name VARCHAR(255) AFTER email";
    }

    if (!in_array('contact_phone', $columns)) {
        $columnsToAdd[] = "ADD COLUMN contact_phone VARCHAR(20) AFTER contact_name";
    }

    if (!in_array('discount_amount', $columns)) {
        $columnsToAdd[] = "ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER shipping_amount";
    }

    if (!in_array('address_id', $columns)) {
        $columnsToAdd[] = "ADD COLUMN address_id INT AFTER email";
        $columnsToAdd[] = "ADD CONSTRAINT fk_orders_address_id FOREIGN KEY (address_id) REFERENCES customer_addresses(id) ON DELETE SET NULL";
    }

    if (empty($columnsToAdd)) {
        echo "All required columns already exist in orders table.\n";
        exit(0);
    }

    // Execute ALTER TABLE statements
    foreach ($columnsToAdd as $alterSql) {
        echo "Executing: ALTER TABLE orders $alterSql\n";
        $pdo->exec("ALTER TABLE orders $alterSql");
    }

    echo "Database update completed successfully!\n";
    echo "Added columns: " . implode(', ', array_map(function($col) {
        return preg_replace('/^(ADD COLUMN |ADD CONSTRAINT .* FOREIGN KEY \()(.*?)(\)| .*$)/', '$2', $col);
    }, $columnsToAdd)) . "\n";

} catch (PDOException $e) {
    echo "Database update failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
