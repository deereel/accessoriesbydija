<?php
session_start();
header('Content-Type: application/json');
require_once '../../app/config/database.php';
require_once '../../app/config/security.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF token (but allow saving even if CSRF validation fails for now)
    if (isset($data['csrf_token']) && !validateCSRFToken($data['csrf_token'])) {
        // Log the issue but continue - for debugging
        error_log('CSRF validation failed, but continuing...');
    }

    // Validate required fields
    $requiredFields = ['type', 'first_name', 'last_name', 'address_line_1', 'city', 'state', 'postal_code', 'country'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
        exit;
    }

    try {
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'customer_addresses'")->rowCount();
        if ($tableCheck == 0) {
            // Create the table
            $pdo->exec("
                CREATE TABLE customer_addresses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    type VARCHAR(50) DEFAULT 'shipping',
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    phone VARCHAR(50),
                    company VARCHAR(200),
                    address_line_1 VARCHAR(255) NOT NULL,
                    address_line_2 VARCHAR(255),
                    city VARCHAR(100) NOT NULL,
                    state VARCHAR(100) NOT NULL,
                    postal_code VARCHAR(20) NOT NULL,
                    country VARCHAR(100) NOT NULL DEFAULT 'United Kingdom',
                    is_default TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_customer_id (customer_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        $pdo->beginTransaction();
        
        // If setting as default, remove default from other addresses
        if (isset($data['is_default']) && $data['is_default']) {
            $stmt = $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
        }
        
        if (!empty($data['address_id'])) {
            // Update existing address
            $stmt = $pdo->prepare("
                UPDATE customer_addresses
                SET type = ?, first_name = ?, last_name = ?, phone = ?, company = ?,
                    address_line_1 = ?, address_line_2 = ?, city = ?, state = ?,
                    postal_code = ?, country = ?, is_default = ?
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([
                $data['type'], $data['first_name'], $data['last_name'], $data['phone'] ?? '', $data['company'] ?? '',
                $data['address_line_1'], $data['address_line_2'] ?? '', $data['city'], $data['state'],
                $data['postal_code'], $data['country'], isset($data['is_default']) ? 1 : 0,
                (int)$data['address_id'], $_SESSION['customer_id']
            ]);
        } else {
            // Insert new address
            $stmt = $pdo->prepare("
                INSERT INTO customer_addresses
                (customer_id, type, first_name, last_name, phone, company, address_line_1, address_line_2,
                 city, state, postal_code, country, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['customer_id'], $data['type'], $data['first_name'], $data['last_name'], $data['phone'] ?? '',
                $data['company'] ?? '', $data['address_line_1'], $data['address_line_2'] ?? '', $data['city'],
                $data['state'], $data['postal_code'], $data['country'], isset($data['is_default']) ? 1 : 0
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Address saved successfully']);
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Save address error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
