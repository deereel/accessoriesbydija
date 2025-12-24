<?php
/**
 * Inventory Diagnostics
 * GET /api/debug/inventory-diagnostics.php?key=your-admin-key
 *
 * Returns JSON with table existence, row counts, and recent entries.
 */

header('Content-Type: application/json');

$admin_key = $_GET['key'] ?? null;
$expected_key = getenv('ADMIN_SETUP_KEY') ?: 'setup_key_12345';

if ($admin_key !== $expected_key) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $tables = ['inventory_transactions', 'inventory_logs'];
    $result = ['success' => true, 'tables' => []];

    foreach ($tables as $table) {
        $check = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
        $exists = ($check->fetch(PDO::FETCH_NUM)[0] > 0);
        $entry = ['exists' => $exists];

        if ($exists) {
            // row count
            $countStmt = $pdo->query("SELECT COUNT(*) as c FROM $table");
            $entry['row_count'] = (int)$countStmt->fetchColumn();

            // recent rows (limit 10)
            $rowsStmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
            $entry['recent'] = $rows;
        }

        $result['tables'][$table] = $entry;
    }

    // Also return a sample product stock snapshot for last 5 products
    $prodStmt = $pdo->query("SELECT id, name, stock_quantity FROM products ORDER BY id DESC LIMIT 5");
    $result['recent_products'] = $prodStmt ? $prodStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode($result, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>