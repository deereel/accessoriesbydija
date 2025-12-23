<?php
/**
 * Check if required inventory tables exist in database
 * GET /api/setup/check-tables.php
 * 
 * Returns: { "success": bool, "tables": { "inventory_transactions": bool, "inventory_logs": bool } }
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

try {
    $tables = [
        'inventory_transactions' => false,
        'inventory_logs' => false
    ];
    
    foreach ($tables as $table_name => $exists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_name'");
        $result = $stmt->fetch(PDO::FETCH_NUM);
        $tables[$table_name] = ($result[0] > 0);
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'all_present' => !in_array(false, $tables)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
