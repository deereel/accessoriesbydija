<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'superadmin') {
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

if (!file_exists($log_path)) {
    echo json_encode(['success' => true, 'logs' => []]);
    exit;
}

$logs = [];
$lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    // Parse Monolog format: [timestamp] channel.level: message
    if (preg_match('/^\[([^\]]+)\]\s+([^\.]+)\.(\w+):\s+(.+)$/', $line, $matches)) {
        $logs[] = [
            'timestamp' => $matches[1],
            'channel' => $matches[2],
            'level' => $matches[3],
            'message' => $matches[4]
        ];
    } else {
        // Fallback for other formats
        $logs[] = [
            'timestamp' => '',
            'channel' => '',
            'level' => 'info',
            'message' => $line
        ];
    }
}

// Return last 1000 entries
$logs = array_slice($logs, -1000);

echo json_encode(['success' => true, 'logs' => $logs]);
?>