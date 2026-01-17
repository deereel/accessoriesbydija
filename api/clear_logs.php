<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$file = $_GET['file'] ?? 'app.log';

// Validate file name
$allowed_files = ['app.log', 'app_dev.log'];
if (!in_array($file, $allowed_files)) {
    echo json_encode(['success' => false, 'message' => 'Invalid log file']);
    exit;
}

$log_path = __DIR__ . '/../logs/' . $file;

if (file_exists($log_path)) {
    if (file_put_contents($log_path, '') === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to clear log file']);
        exit;
    }
}

echo json_encode(['success' => true, 'message' => 'Log file cleared successfully']);
?>