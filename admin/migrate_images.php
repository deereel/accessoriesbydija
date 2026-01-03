<?php
require_once '../config/database.php';

try {
    // Add tag column to product_images table
    $pdo->exec("ALTER TABLE product_images ADD COLUMN tag VARCHAR(8) NULL AFTER variant_id");
    $pdo->exec("ALTER TABLE product_images ADD INDEX idx_tag (tag)");
    
    echo "Migration completed successfully: Added tag column to product_images table\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
?>