<?php
require_once 'config/database.php';

try {
    // Check if tables exist
    $tables = [
        'products', 'categories', 'customers', 'orders', 'order_items',
        'product_variants', 'materials', 'colors', 'sizes', 'adornments'
    ];

    $missing_tables = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }

    if (empty($missing_tables)) {
        echo "✅ Database is fully set up with all required tables.\n";
    } else {
        echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n";
        echo "Please run database.sql to set up the database.\n";
    }

    // Check sample data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $product_count = $stmt->fetch()['count'];
    echo "Products in database: $product_count\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $category_count = $stmt->fetch()['count'];
    echo "Categories in database: $category_count\n";

} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?>