<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    echo json_encode(['success'=>true,'ticket'=>$ticket]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'DB error']);
}

?>
