<?php
// Script to update client_image column to MEDIUMBLOB for storing base64 images
require_once 'config/database.php';

try {
    // Check if column exists and get its type
    $stmt = $pdo->query("SHOW COLUMNS FROM testimonials LIKE 'client_image'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        // Add the client_image column as MEDIUMTEXT for base64 data
        $pdo->exec("ALTER TABLE testimonials ADD COLUMN client_image MEDIUMTEXT NULL AFTER product_id");
        echo "✓ Successfully added 'client_image' column (MEDIUMTEXT) to testimonials table.<br>";
    } else if ($column['Type'] !== 'mediumtext') {
        // Modify existing column to MEDIUMTEXT
        $pdo->exec("ALTER TABLE testimonials MODIFY COLUMN client_image MEDIUMTEXT NULL");
        echo "✓ Successfully modified 'client_image' column to MEDIUMTEXT for base64 storage.<br>";
    } else {
        echo "✓ Column 'client_image' already exists with correct type (MEDIUMTEXT).<br>";
    }
    
    echo "<br>Database update completed successfully!<br>";
    echo "Images will now be stored as base64 encoded data directly in the database.";
    
} catch (PDOException $e) {
    echo "✗ Error updating database: " . $e->getMessage();
}
?>