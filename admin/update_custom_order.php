<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : null;
$estimated_price = isset($_POST['estimated_price']) ? trim($_POST['estimated_price']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id is required']);
    exit;
}

$allowed_statuses = ['new', 'in_progress', 'completed', 'cancelled'];

try {
    // Ensure order exists
    $existsStmt = $pdo->prepare("SELECT id, status FROM custom_requests WHERE id = ?");
    $existsStmt->execute([$order_id]);
    $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Custom order not found']);
        exit;
    }

    $fields = [];
    $params = [];

    if ($status !== null && $status !== '') {
        $status = strtolower($status);
        if (!in_array($status, $allowed_statuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }

        $fields[] = "status = ?";
        $params[] = $status;
    }

    if ($estimated_price !== null && $estimated_price !== '') {
        $estimated_price = (float)$estimated_price;
        if ($estimated_price < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid estimated price']);
            exit;
        }
        $fields[] = "estimated_price = ?";
        $params[] = $estimated_price;
    }

    if ($notes !== null) {
        $fields[] = "notes = ?";
        $params[] = $notes;
    }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'No changes']);
        exit;
    }

    $sql = "UPDATE custom_requests SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
    $params[] = $order_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Custom order updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
