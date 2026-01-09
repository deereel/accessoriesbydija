<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT id FROM product_variations WHERE tag = 'MMJ01-2'");
    $id = $stmt->fetchColumn();
    echo "ID for 'MMJ01-2': $id";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}