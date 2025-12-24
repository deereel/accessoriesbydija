<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['admin_role'] ?? '') !== 'superadmin') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized - superadmin access required']); exit;
}
require_once __DIR__ . '/../config/database.php';

try {
    // Create support_tickets table directly
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_id INT,
            customer_email VARCHAR(255) NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            category VARCHAR(100),
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            assigned_to INT,
            response_text TEXT,
            response_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX (customer_id, status),
            INDEX (status, created_at),
            INDEX (priority)
        )
    ");
    
    // Create inventory_transactions table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            transaction_type ENUM('purchase', 'sale', 'adjustment', 'return') DEFAULT 'sale',
            quantity_change INT NOT NULL,
            reference_id INT,
            reference_type VARCHAR(50),
            notes TEXT,
            previous_stock INT,
            new_stock INT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX (product_id, created_at),
            INDEX (reference_type, reference_id)
        )
    ");
    
    // Create inventory_logs table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            quantity_change INT,
            old_quantity INT,
            new_quantity INT,
            user_id INT,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX (product_id, created_at)
        )
    ");
    
    // Add missing columns to inventory_logs if they don't exist
    $columns_added = [];
    
    $qtyChangeCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'inventory_logs' AND column_name = 'quantity_change'");
    $qtyChangeCheck->execute();
    if ($qtyChangeCheck->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE inventory_logs ADD COLUMN quantity_change INT AFTER action");
        $columns_added[] = 'inventory_logs.quantity_change';
    }
    
    $reasonCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'inventory_logs' AND column_name = 'reason'");
    $reasonCheck->execute();
    if ($reasonCheck->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE inventory_logs ADD COLUMN reason TEXT AFTER user_id");
        $columns_added[] = 'inventory_logs.reason';
    }
    
    // Add force_password_reset column to customers if missing
    $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'force_password_reset'");
    $colCheck->execute();
    if ($colCheck->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN force_password_reset TINYINT(1) DEFAULT 0");
        $columns_added[] = 'customers.force_password_reset';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'All required tables created/verified successfully',
        'tables_created' => ['support_tickets', 'inventory_transactions', 'inventory_logs'],
        'columns_added' => $columns_added
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>
