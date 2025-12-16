<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

require_once '../config/database.php';

// --- Database Backup Utility ---
$backups_dir = __DIR__ . '/backups';
if (!is_dir($backups_dir)) {
    mkdir($backups_dir, 0755, true);
}

// Get database name from DSN
preg_match('/dbname=([^;]+)/', $dsn, $matches);
$db_name = $matches[1] ?? 'database';
$backup_file_name = $db_name . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
$backup_file_path = $backups_dir . '/' . $backup_file_name;

$error_message = null;

try {
    $handle = fopen($backup_file_path, 'w');
    if (!$handle) {
        throw new Exception("Could not open file for writing: " . $backup_file_path);
    }

    function write_to_stream($handle, $string) {
        fwrite($handle, $string . "\n");
    }

    write_to_stream($handle, "-- Dija Accessories Database Backup\n");
    write_to_stream($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
    write_to_stream($handle, "-- --------------------------------------\n");

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if ($table === 'database_backups') continue; // Don't include the backups table in the backup

        write_to_stream($handle, "\n-- Table structure for table `$table`\n");
        $create_table_stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $create_table_row = $create_table_stmt->fetch(PDO::FETCH_ASSOC);
        write_to_stream($handle, "DROP TABLE IF EXISTS `$table`;");
        write_to_stream($handle, $create_table_row['Create Table'] . ";\n");
        
        $data_stmt = $pdo->query("SELECT * FROM `$table`");
        if ($data_stmt->rowCount() > 0) {
            write_to_stream($handle, "\n-- Dumping data for table `$table`\n");
            while ($row = $data_stmt->fetch(PDO::FETCH_ASSOC)) {
                $insert_sql = "INSERT INTO `$table` (`" . implode("`, `", array_keys($row)) . "`) VALUES (";
                $values = [];
                foreach ($row as $value) {
                    $values[] = isset($value) ? $pdo->quote($value) : "NULL";
                }
                $insert_sql .= implode(', ', $values) . ");";
                write_to_stream($handle, $insert_sql);
            }
        }
    }

    write_to_stream($handle, "\n-- Backup completed on " . date('Y-m-d H:i:s') . "\n");
    fclose($handle);

    // Log to database
    $filesize = filesize($backup_file_path);
    $admin_user_id = $_SESSION['admin_user_id'] ?? null;

    $log_stmt = $pdo->prepare("INSERT INTO database_backups (filename, filesize, user_id) VALUES (?, ?, ?)");
    $log_stmt->execute([$backup_file_name, $filesize, $admin_user_id]);

    $_SESSION['flash_message'] = "Successfully created backup: " . htmlspecialchars($backup_file_name);

} catch (Exception $e) {
    $error_message = "Backup failed: " . $e->getMessage();
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
    if (file_exists($backup_file_path)) {
        unlink($backup_file_path);
    }
    $_SESSION['flash_error'] = $error_message;
}

header('Location: database.php');
exit;