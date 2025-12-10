<?php
// Test database connection and data
try {
    require_once 'config/database.php';
    
    echo "<h2>Database Connection Test</h2>";
    
    // Test connection
    if (isset($pdo)) {
        echo "✓ Database connection successful<br>";
        
        // Check if products table exists
        $tables = $pdo->query("SHOW TABLES LIKE 'products'")->fetchAll();
        if (empty($tables)) {
            echo "✗ Products table does not exist<br>";
            echo "<a href='database.php'>Create tables</a><br>";
        } else {
            echo "✓ Products table exists<br>";
            
            // Count products
            $count = $pdo->query("SELECT COUNT(*) as total FROM products")->fetch();
            echo "Total products: {$count['total']}<br>";
            
            if ($count['total'] == 0) {
                echo "✗ No products in database<br>";
                echo "<a href='database.php'>Add sample data</a><br>";
            } else {
                echo "✓ Products found in database<br>";
                
                // Show first few products
                $products = $pdo->query("SELECT id, name, price FROM products LIMIT 5")->fetchAll();
                echo "<h3>Sample Products:</h3>";
                foreach ($products as $product) {
                    echo "ID: {$product['id']}, Name: {$product['name']}, Price: £{$product['price']}<br>";
                }
            }
        }
    } else {
        echo "✗ Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>