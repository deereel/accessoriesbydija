<?php
/**
 * Database Setup/Migration Script
 * GET /api/setup/init-db.php?key=your-admin-key
 * 
 * Creates required tables if they don't exist
 * Returns: { "success": bool, "tables_created": array, "tables_exist": array }
 */

header('Content-Type: application/json');

// Simple security check - require an admin key
$admin_key = $_GET['key'] ?? null;
$expected_key = getenv('ADMIN_SETUP_KEY') ?: 'setup_key_12345';

if ($admin_key !== $expected_key) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

require_once __DIR__ . '/../../app/config/database.php';

try {
    // Array of CREATE TABLE statements for inventory tables
    $tables = [
        'inventory_transactions' => "
            CREATE TABLE IF NOT EXISTS inventory_transactions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                transaction_type ENUM('purchase', 'sale', 'adjustment', 'return', 'cancellation') DEFAULT 'sale',
                quantity_change INT NOT NULL,
                reference_id INT,
                reference_type VARCHAR(50),
                notes TEXT,
                previous_stock INT,
                new_stock INT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                INDEX (product_id, created_at),
                INDEX (reference_type, reference_id)
            )
        ",
        'inventory_logs' => "
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
                INDEX (product_id, created_at),
                INDEX (action)
            )
        "
    ];
    
    $tables_created = [];
    $tables_exist = [];
    
    foreach ($tables as $table_name => $sql) {
        // Check if table exists
        $check = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_name'");
        $exists = ($check->fetch(PDO::FETCH_NUM)[0] > 0);
        
        if ($exists) {
            $tables_exist[] = $table_name;
        } else {
            // Try to create table
            $pdo->exec($sql);
            $tables_created[] = $table_name;
        }
    }
    
    echo json_encode([
        'success' => true,
        'tables_created' => $tables_created,
        'tables_exist' => $tables_exist,
        'message' => count($tables_created) === 0 ? 'All required tables already exist' : 'Created tables: ' . implode(', ', $tables_created)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
