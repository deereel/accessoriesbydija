<?php
// Simple migration runner: executes SQL statements from a file using project's DB config.
if (php_sapi_name() !== 'cli') {
    echo "Run from CLI: php run_sql_migration.php\n";
    exit(1);
}
require_once __DIR__ . '/../app/config/database.php';
$sqlFile = __DIR__ . '/../sql/create_analytics_and_email_logs.sql';
if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}
$sql = file_get_contents($sqlFile);
try {
    // Split statements by semicolon at line breaks conservatively
    $stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
    foreach ($stmts as $s) {
        if (!trim($s)) continue;
        $pdo->exec($s);
        echo "OK: executed statement\n";
    }
    echo "Migration completed.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>