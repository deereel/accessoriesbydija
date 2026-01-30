<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../app/config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $customer_id = isset($data['customer_id']) ? (int)$data['customer_id'] : 0;
    $action = ($data['action'] ?? 'set'); // set or clear
    if (!$customer_id) { echo json_encode(['success'=>false,'message'=>'Missing customer_id']); exit; }

    // Ensure customers table has the column
    $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'force_password_reset'");
    $colCheck->execute();
    $hasCol = $colCheck->fetchColumn() > 0;
    if (!$hasCol) {
        try {
            $pdo->exec("ALTER TABLE customers ADD COLUMN force_password_reset TINYINT(1) DEFAULT 0");
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>'Failed to add column: ' . $e->getMessage()]); exit;
        }
    }

    $val = ($action === 'clear') ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE customers SET force_password_reset = ? WHERE id = ?");
    $stmt->execute([$val, $customer_id]);

    echo json_encode(['success'=>true,'message'=>'Flag updated','customer_id'=>$customer_id,'force_password_reset'=>$val]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}

?>
