<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/security.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF token
    if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // If setting as default, remove default from other addresses
        if ($data['is_default']) {
            $stmt = $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
        }
        
        if (!empty($data['address_id'])) {
            // Update existing address
            $stmt = $pdo->prepare("
                UPDATE customer_addresses 
                SET type = ?, first_name = ?, last_name = ?, company = ?, 
                    address_line_1 = ?, address_line_2 = ?, city = ?, state = ?, 
                    postal_code = ?, country = ?, is_default = ?
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([
                $data['type'], $data['first_name'], $data['last_name'], $data['company'],
                $data['address_line_1'], $data['address_line_2'], $data['city'], $data['state'],
                $data['postal_code'], $data['country'], $data['is_default'],
                $data['address_id'], $_SESSION['customer_id']
            ]);
        } else {
            // Insert new address
            $stmt = $pdo->prepare("
                INSERT INTO customer_addresses 
                (customer_id, type, first_name, last_name, company, address_line_1, address_line_2, 
                 city, state, postal_code, country, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['customer_id'], $data['type'], $data['first_name'], $data['last_name'],
                $data['company'], $data['address_line_1'], $data['address_line_2'], $data['city'],
                $data['state'], $data['postal_code'], $data['country'], $data['is_default']
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Address saved successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>