<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Get current password hash
        $stmt = $pdo->prepare("SELECT password_hash FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify current password
        if (!password_verify($data['current_password'], $customer['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        // Update password
        $new_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE customers SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $_SESSION['customer_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>