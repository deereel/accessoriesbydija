<?php
// Run: php create_wishlists_table.php
require_once __DIR__ . '/config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS wishlists (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist_item (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Wishlists table created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating wishlists table: " . $e->getMessage() . "\n";
}
?>