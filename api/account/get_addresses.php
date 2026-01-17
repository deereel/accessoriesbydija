<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    if (isset($_GET['id'])) {
        // Get single address
        $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ?");
        $stmt->execute([(int)$_GET['id'], $_SESSION['customer_id']]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'address' => $address]);
    } else {
        // Get all addresses
        $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$_SESSION['customer_id']]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'addresses' => $addresses]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>