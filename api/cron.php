<?php
/**
 * Cron API - For scheduled tasks
 * Can be called by real cron jobs or used with pseudo-cron
 * 
 * Cron job setup (run weekly on Sunday at 2 AM):
 * 0 2 * * 0 curl https://accessoriesbydija.uk/api/cron.php?key=YOUR_CRON_KEY >> /var/log/cron.log 2>&1
 */

require_once '../app/config/database.php';
require_once '../app/config/env.php';

// Get cron key from config
$cron_key = defined('CRON_KEY') ? CRON_KEY : getenv('CRON_KEY') ?: 'default-cron-key-change-this';

// Check cron key
$provided_key = $_GET['key'] ?? $_POST['key'] ?? null;
if ($provided_key !== $cron_key) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid cron key']);
    exit;
}

header('Content-Type: application/json');

$results = [];

/**
 * Run weekly database backup
 */
function runWeeklyBackup() {
    global $pdo, $results;
    
    $backups_dir = __DIR__ . '/../admin/backups';
    if (!is_dir($backups_dir)) {
        mkdir($backups_dir, 0755, true);
    }
    
    // Get database name from DSN
    preg_match('/dbname=([^;]+)/', $GLOBALS['dsn'], $matches);
    $db_name = $matches[1] ?? 'database';
    $backup_file_name = $db_name . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_file_path = $backups_dir . '/' . $backup_file_name;
    
    $error = null;
    
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
        
        $tables = $GLOBALS['pdo']->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            if ($table === 'database_backups') continue;
            
            write_to_stream($handle, "\n-- Table structure for table `$table`\n");
            $create_table_stmt = $GLOBALS['pdo']->query("SHOW CREATE TABLE `$table`");
            $create_table_row = $create_table_stmt->fetch(PDO::FETCH_ASSOC);
            write_to_stream($handle, "DROP TABLE IF EXISTS `$table`;");
            write_to_stream($handle, $create_table_row['Create Table'] . ";\n");
            
            $data_stmt = $GLOBALS['pdo']->query("SELECT * FROM `$table`");
            if ($data_stmt->rowCount() > 0) {
                write_to_stream($handle, "\n-- Dumping data for table `$table`\n");
                while ($row = $data_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $insert_sql = "INSERT INTO `$table` (`" . implode("`, `", array_keys($row)) . "`) VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = isset($value) ? $GLOBALS['pdo']->quote($value) : "NULL";
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
        $log_stmt = $pdo->prepare('INSERT INTO database_backups (filename, filesize, user_id) VALUES (?, ?, ?)');
        $log_stmt->execute([$backup_file_name, $filesize, 0]); // 0 = system/cron
        
        // Update last run timestamp file
        $last_run_file = __DIR__ . '/../admin/backups/.last_weekly_backup';
        file_put_contents($last_run_file, time());
        
        $results['backup'] = [
            'success' => true,
            'file' => $backup_file_name,
            'size' => $filesize
        ];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        if (isset($handle) && is_resource($handle)) {
            fclose($handle);
        }
        if (file_exists($backup_file_path)) {
            unlink($backup_file_path);
        }
        $results['backup'] = [
            'success' => false,
            'error' => $error
        ];
    }
}

/**
 * Check if weekly backup is needed
 */
function needsWeeklyBackup() {
    $last_run_file = __DIR__ . '/../admin/backups/.last_weekly_backup';
    
    if (!file_exists($last_run_file)) {
        return true;
    }
    
    $last_run = (int)file_get_contents($last_run_file);
    $one_week = 7 * 24 * 60 * 60; // 7 days in seconds
    
    return (time() - $last_run) >= $one_week;
}

/**
 * Get next scheduled run time
 */
function getNextBackupTime() {
    $last_run_file = __DIR__ . '/../admin/backups/.last_weekly_backup';
    
    if (!file_exists($last_run_file)) {
        return 'Now';
    }
    
    $last_run = (int)file_get_contents($last_run_file);
    $next_run = $last_run + (7 * 24 * 60 * 60);
    
    return date('Y-m-d H:i:s', $next_run);
}

// Process request
$action = $_GET['action'] ?? $_POST['action'] ?? 'run';

switch ($action) {
    case 'run':
        // Run the weekly backup
        runWeeklyBackup();
        break;
        
    case 'check':
        // Just check if backup is needed, don't run
        $results['check'] = [
            'backup_needed' => needsWeeklyBackup(),
            'next_backup' => getNextBackupTime()
        ];
        break;
        
    case 'run-if-needed':
        // Run only if it's been a week
        if (needsWeeklyBackup()) {
            runWeeklyBackup();
        } else {
            $results['backup'] = [
                'success' => false,
                'message' => 'Backup not yet due. Next backup: ' . getNextBackupTime()
            ];
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results
]);
