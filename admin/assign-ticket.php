<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../app/config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $ticket_id = isset($data['ticket_id']) ? (int)$data['ticket_id'] : 0;
    $admin_id = isset($data['admin_id']) ? (int)$data['admin_id'] : null;
    if (!$ticket_id) { echo json_encode(['success'=>false,'message'=>'Missing ticket_id']); exit; }

    $stmt = $pdo->prepare("UPDATE support_tickets SET assigned_to = ? WHERE id = ?");
    $stmt->execute([$admin_id, $ticket_id]);

    echo json_encode(['success'=>true,'message'=>'Ticket assigned','ticket_id'=>$ticket_id,'assigned_to'=>$admin_id]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}

?>
