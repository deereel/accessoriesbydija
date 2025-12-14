<?php
require_once 'config/database.php';

try {
    // Add remember_token column to customers table if it doesn't exist
    $pdo->exec("ALTER TABLE customers ADD COLUMN remember_token VARCHAR(64) NULL");

    echo "Database updated successfully! The 'remember_token' column has been added to the customers table.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
