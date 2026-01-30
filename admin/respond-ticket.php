<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../app/config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $ticket_id = isset($data['ticket_id']) ? (int)$data['ticket_id'] : 0;
    $response = trim($data['response'] ?? '');
    $status = in_array($data['status'] ?? '', ['in_progress','resolved','closed']) ? $data['status'] : 'in_progress';
    if (!$ticket_id || $response === '') { echo json_encode(['success'=>false,'message'=>'Missing data']); exit; }

    $stmt = $pdo->prepare("UPDATE support_tickets SET response_text = ?, response_date = NOW(), status = ?, assigned_to = ? WHERE id = ?");
    $stmt->execute([$response, $status, $_SESSION['admin_user_id'] ?? null, $ticket_id]);

    echo json_encode(['success'=>true, 'message'=>'Response saved']);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}

?>
