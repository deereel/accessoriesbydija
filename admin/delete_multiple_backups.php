<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('This action requires POST request.');
}

require_once '../app/config/database.php';

$backups_dir = __DIR__ . '/backups';

// Get backup IDs to delete
$backup_ids = $_POST['backup_ids'] ?? [];

if (empty($backup_ids)) {
    $_SESSION['flash_error'] = 'No backups selected for deletion.';
    header('Location: database.php');
    exit;
}

$deleted_count = 0;
$errors = [];

// Convert to array if single value
if (!is_array($backup_ids)) {
    $backup_ids = [$backup_ids];
}

// Sanitize IDs
$backup_ids = array_map('intval', $backup_ids);

// Fetch backup records from database
try {
    $placeholders = implode(',', array_fill(0, count($backup_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, filename FROM database_backups WHERE id IN ($placeholders)");
    $stmt->execute($backup_ids);
    $backups_to_delete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($backups_to_delete as $backup) {
        $filename = $backup['filename'];
        $backup_id = $backup['id'];
        
        // Delete file from disk
        $file_path = $backups_dir . '/' . $filename;
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                // Delete record from database
                $delete_stmt = $pdo->prepare("DELETE FROM database_backups WHERE id = ?");
                $delete_stmt->execute([$backup_id]);
                $deleted_count++;
            } else {
                $errors[] = 'Could not delete file: ' . $filename;
            }
        } else {
            // File doesn't exist, just delete the record
            $delete_stmt = $pdo->prepare("DELETE FROM database_backups WHERE id = ?");
            $delete_stmt->execute([$backup_id]);
            $deleted_count++;
        }
    }
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Set flash message
if ($deleted_count > 0) {
    $_SESSION['flash_message'] = "Successfully deleted $deleted_count backup(s).";
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode('<br>', $errors);
}

header('Location: database.php');
exit;
