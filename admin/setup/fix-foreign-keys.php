<?php
/**
 * Fix Foreign Key Constraints
 * Run this script to fix all the product variant tables with correct foreign key references
 */

require_once __DIR__ . '/../../app/config/database.php';

try {
    echo "<h1>Fixing Foreign Key Constraints</h1>";
    echo "<pre>";
    
    // Tables to fix - map of table name to correct referenced table
    $tables = [
        'product_adornments' => 'product_variations',
        'product_colors' => 'product_variations',
        'product_materials' => 'product_variations',
        'variant_materials' => 'product_variations',
        'variant_tags' => 'product_variations',
        'variant_stock' => 'product_variations',
    ];
    
    foreach ($tables as $table => $reference) {
        echo "Dropping {$table} table...\n";
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Creating {$table} table...\n";
        $pdo->exec("
            CREATE TABLE `$table` (
              `id` int NOT NULL AUTO_INCREMENT,
              `product_id` int NOT NULL,
              `variant_id` int NOT NULL,
              `adornment_id` int DEFAULT NULL,
              `color_id` int DEFAULT NULL,
              `material_id` int DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `fk_{$table}_product` (`product_id`),
              KEY `fk_{$table}_variant` (`variant_id`),
              CONSTRAINT `fk_{$table}_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_{$table}_variant` FOREIGN KEY (`variant_id`) REFERENCES `$reference` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ {$table} table created successfully\n\n";
    }
    
    echo "========================================\n";
    echo "✓ All foreign key constraints fixed!\n";
    echo "========================================\n";
    echo "</pre>";
    echo "<p><a href='../categories.php'>Return to Categories</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
