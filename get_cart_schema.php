<?php
require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>