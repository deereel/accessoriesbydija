<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['event'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$event = substr(trim($input['event']), 0, 100);
$payload = isset($input['payload']) ? json_encode($input['payload']) : null;
$user_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare('INSERT INTO analytics_events (event_name, payload, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$event, $payload, $user_id, $ip]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // fail silently but return failure so client can fallback
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to record event']);
}
?>