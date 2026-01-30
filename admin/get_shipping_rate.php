<?php
require_once '../app/config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Rate ID required']);
    exit;
}

$rate_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM shipping_rates WHERE id = ?");
    $stmt->execute([$rate_id]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rate) {
        echo json_encode(['error' => 'Shipping rate not found']);
        exit;
    }

    echo json_encode($rate);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
