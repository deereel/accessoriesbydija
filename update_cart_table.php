<?php
// Run: php update_cart_table.php
require_once __DIR__ . '/config/database.php';

try {
    // Drop foreign keys temporarily
    $pdo->exec("ALTER TABLE cart DROP FOREIGN KEY cart_ibfk_1");
    $pdo->exec("ALTER TABLE cart DROP FOREIGN KEY cart_ibfk_2");
    echo "Dropped foreign keys.\n";
} catch (PDOException $e) {
    echo "Error dropping foreign keys: " . $e->getMessage() . "\n";
}

try {
    // Drop the unique key
    $pdo->exec("ALTER TABLE cart DROP KEY unique_cart_item");
    echo "Dropped unique key.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'check that column/key exists') !== false) {
        echo "Key already dropped.\n";
    } else {
        echo "Error dropping key: " . $e->getMessage() . "\n";
    }
}

try {
    // Add back foreign keys
    $pdo->exec("ALTER TABLE cart ADD CONSTRAINT cart_ibfk_1 FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE cart ADD CONSTRAINT cart_ibfk_2 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE");
    echo "Added back foreign keys.\n";
} catch (PDOException $e) {
    echo "Error adding foreign keys: " . $e->getMessage() . "\n";
}

try {
    // Add missing columns
    $alterSql = "ALTER TABLE cart
        ADD COLUMN material_id INT DEFAULT NULL,
        ADD COLUMN variation_id INT DEFAULT NULL,
        ADD COLUMN size_id INT DEFAULT NULL,
        ADD COLUMN selected_price DECIMAL(10,2) DEFAULT NULL";
    $pdo->exec($alterSql);
    echo "Added missing columns.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error adding columns: " . $e->getMessage() . "\n";
    }
}

try {
    $sql = "CREATE TABLE IF NOT EXISTS cart (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        material_id INT DEFAULT NULL,
        variation_id INT DEFAULT NULL,
        size_id INT DEFAULT NULL,
        selected_price DECIMAL(10,2) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Cart table created/verified.\n";
} catch (PDOException $e) {
    echo "Error creating cart table: " . $e->getMessage() . "\n";
}
?>