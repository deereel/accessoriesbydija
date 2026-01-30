<?php
require_once '../config/database.php';

try {
    $pdo->exec("
        ALTER TABLE products
        ADD COLUMN gender VARCHAR(50) NOT NULL DEFAULT 'Unisex' AFTER stock_quantity,
        ADD COLUMN category_id INT(11) UNSIGNED AFTER gender,
        ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;
    ");
    echo "Migration successfully executed!";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
