<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

require_once '../app/config/database.php';

$backup_id = $_GET['id'] ?? 0;
if ($backup_id <= 0) {
    $_SESSION['flash_error'] = 'Invalid backup ID.';
    header('Location: database.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Find the backup record
    $stmt = $pdo->prepare("SELECT filename FROM database_backups WHERE id = ?");
    $stmt->execute([$backup_id]);
    $filename = $stmt->fetchColumn();

    if (!$filename) {
        throw new Exception('Backup record not found.');
    }
    
    // Security check
    if (basename($filename) !== $filename) {
        throw new Exception('Invalid filename.');
    }

    // Delete the physical file
    $backups_dir = __DIR__ . '/backups';
    $file_path = $backups_dir . '/' . $filename;

    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            throw new Exception('Could not delete the backup file. Check permissions.');
        }
    }

    // Delete the database record
    $delete_stmt = $pdo->prepare("DELETE FROM database_backups WHERE id = ?");
    $delete_stmt->execute([$backup_id]);

    $pdo->commit();
    $_SESSION['flash_message'] = 'Successfully deleted backup: ' . htmlspecialchars($filename);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
}

header('Location: database.php');
exit;
