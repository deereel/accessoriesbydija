<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied']);
    exit;
}

require_once '../app/config/database.php';

$response = ['success' => false];
$user_id = $_GET['id'] ?? 0;

if ($user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, is_active FROM admin_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $response['success'] = true;
            $response['user'] = $user;
        } else {
            $response['error'] = 'User not found.';
        }
    } catch (PDOException $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
        http_response_code(500);
    }
} else {
    $response['error'] = 'Invalid user ID.';
    http_response_code(400);
}

echo json_encode($response);
exit;
