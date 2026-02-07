<?php
session_start();
header('Content-Type: application/json');
require_once '../../app/config/database.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if table exists first
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'customer_addresses'")->rowCount();
        if ($tableCheck == 0) {
            echo json_encode(['success' => false, 'message' => 'No addresses table exists']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM customer_addresses WHERE id = ? AND customer_id = ?");
        $stmt->execute([$data['address_id'], $_SESSION['customer_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
    } catch (PDOException $e) {
        error_log('Delete address error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
