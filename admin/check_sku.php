<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$base = $input['base'] ?? '';

if (!$base) {
    echo json_encode(['error' => 'Base required']);
    exit;
}

try {
    // Find the next available number for this base
    $stmt = $pdo->prepare("SELECT sku FROM products WHERE sku LIKE ? ORDER BY sku DESC LIMIT 1");
    $stmt->execute([$base . '%']);
    $lastSku = $stmt->fetchColumn();
    
    $number = 1;
    if ($lastSku) {
        // Extract number from last SKU
        preg_match('/(\d+)$/', $lastSku, $matches);
        if ($matches) {
            $number = intval($matches[1]) + 1;
        }
    }
    
    $sku = $base . sprintf('%02d', $number);
    
    echo json_encode(['sku' => $sku]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>