<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : null;
$payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id is required']);
    exit;
}

$allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$allowed_payment_statuses = ['pending', 'paid', 'failed', 'refunded'];

try {
    // Ensure order exists
    $existsStmt = $pdo->prepare("SELECT id, status, payment_status FROM orders WHERE id = ?");
    $existsStmt->execute([$order_id]);
    $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
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

        // Validate allowed transitions
        $current_status = $existing['status'];
        $allowed_transitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => [], // No changes allowed after delivery
            'cancelled' => []  // No changes allowed after cancellation
        ];

        if (!in_array($status, $allowed_transitions[$current_status] ?? [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status transition from ' . $current_status . ' to ' . $status]);
            exit;
        }

        $fields[] = "status = ?";
        $params[] = $status;
    }

    if ($payment_status !== null && $payment_status !== '') {
        $payment_status = strtolower($payment_status);
        if (!in_array($payment_status, $allowed_payment_statuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid payment_status']);
            exit;
        }
        $fields[] = "payment_status = ?";
        $params[] = $payment_status;
    }

    if ($notes !== null) {
        $fields[] = "notes = ?";
        $params[] = $notes;
    }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'No changes']);
        exit;
    }

    $sql = "UPDATE orders SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
    $params[] = $order_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
