<?php
session_start();
require_once 'config/database.php';

echo "<h1>Wishlist Debug Test</h1>";

// Check if wishlists table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'wishlists'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "<p style='color: green;'>✓ wishlists table exists</p>";

        // Check table structure
        $stmt = $pdo->query("DESCRIBE wishlists");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3><ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";

        // Check if user is logged in
        $customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
        echo "<p>Customer ID from session: " . ($customer_id ?: 'Not logged in') . "</p>";

        if ($customer_id) {
            // Check wishlist items
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            $count = $stmt->fetch()['count'];
            echo "<p>Wishlist items for user: $count</p>";
        }

    } else {
        echo "<p style='color: red;'>✗ wishlists table does not exist</p>";
        echo "<p>Please run this SQL in your database:</p>";
        echo "<pre>" . htmlspecialchars("
CREATE TABLE IF NOT EXISTS wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
        ") . "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Test API endpoint
echo "<h2>Test API Call</h2>";
echo "<p>Visit: <a href='api/wishlist.php' target='_blank'>api/wishlist.php</a></p>";
echo "<p>This should return JSON with your wishlist items (if logged in and table exists)</p>";
?>