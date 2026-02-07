<?php
/**
 * Database Backup Script
 * Run via cron: 0 2 * * * php /path/to/scripts/backup_db.php
 */

require_once __DIR__ . '/../app/config/env.php';
require_once __DIR__ . '/../app/config/database.php';

$backup_dir = __DIR__ . '/../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$date = date('Y-m-d_H-i-s');
$filename = "backup_{$date}.sql";
$filepath = $backup_dir . $filename;

// Use mysqldump if available
$command = "mysqldump --host=" . getenv('DB_HOST') . " --user=" . getenv('DB_USER') . " --password=" . getenv('DB_PASSWORD') . " " . getenv('DB_NAME') . " > $filepath";

exec($command, $output, $return_var);

if ($return_var === 0) {
    error_log("Database backup successful: $filename");
    // Optionally send email notification
} else {
    error_log("Database backup failed: $filename");
    // Send alert email
}
?>