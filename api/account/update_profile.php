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
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, 
                date_of_birth = ?, gender = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
            $data['date_of_birth'] ?: null, $data['gender'] ?: null,
            $_SESSION['customer_id']
        ]);
        
        // Update session
        $_SESSION['customer_name'] = $data['first_name'] . ' ' . $data['last_name'];
        $_SESSION['customer_email'] = $data['email'];
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>