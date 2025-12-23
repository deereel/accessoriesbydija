<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $ticket_id = isset($data['ticket_id']) ? (int)$data['ticket_id'] : 0;
    $status = ($data['status'] ?? 'closed');
    if (!$ticket_id) { echo json_encode(['success'=>false,'message'=>'Missing ticket_id']); exit; }

    $stmt = $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $ticket_id]);

    echo json_encode(['success'=>true,'message'=>'Ticket updated','ticket_id'=>$ticket_id,'status'=>$status]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}

?>
