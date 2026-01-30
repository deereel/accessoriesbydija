<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

require_once '../app/config/database.php';

$backup_id = $_GET['id'] ?? 0;
if ($backup_id <= 0) {
    exit('Invalid backup ID.');
}

try {
    $stmt = $pdo->prepare("SELECT filename FROM database_backups WHERE id = ?");
    $stmt->execute([$backup_id]);
    $filename = $stmt->fetchColumn();

    if (!$filename) {
        exit('Backup record not found.');
    }

    // Security check: prevent directory traversal
    if (basename($filename) !== $filename) {
        exit('Invalid filename.');
    }

    $backups_dir = __DIR__ . '/backups';
    $file_path = $backups_dir . '/' . $filename;

    if (!file_exists($file_path)) {
        exit('Backup file not found on server.');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    flush(); // Flush system output buffer
    readfile($file_path);
    exit;

} catch (PDOException $e) {
    exit('Database error: ' . $e->getMessage());
}
