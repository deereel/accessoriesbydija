<?php
require_once 'config/database.php';
try {
    $pdo->exec("UPDATE product_images SET variant_id = 160 WHERE tag = 'MMJ01-2'");
    $pdo->exec("UPDATE product_images SET variant_id = NULL WHERE tag = '' AND variant_id IS NOT NULL");
    echo 'Fixed invalid variant_ids';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}