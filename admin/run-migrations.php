<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['admin_role'] ?? '') !== 'superadmin') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized - superadmin access required']); exit;
}
require_once __DIR__ . '/../config/database.php';

$sqlFiles = [
    __DIR__ . '/../database.sql',
    __DIR__ . '/../database_variations.sql',
    __DIR__ . '/../add_tag_column.sql',
    __DIR__ . '/../sql/2026_01_05_add_gender_and_category_to_products.sql',
    __DIR__ . '/../sql/2026_01_05_add_fk_to_category_id.sql'
];

$content = '';
foreach ($sqlFiles as $sqlFile) {
    if (file_exists($sqlFile)) {
        $content .= "\n" . file_get_contents($sqlFile);
    }
}

if (empty($content)) {
    echo json_encode(['success'=>false,'message'=>'No migration files found']); exit;
}
// Remove CREATE DATABASE and USE statements to avoid privilege issues
$content = preg_replace('/CREATE\s+DATABASE[\s\S]*?;|USE\s+[`\w]+\s*;?/i', '', $content);

$statements = array_filter(array_map('trim', explode(';', $content)));
$results = [];

foreach ($statements as $stmt) {
    // Skip empty or comment-only lines
    if ($stmt === '' || preg_match('/^--/', trim($stmt))) continue;

    $lower = strtolower($stmt);
    try {
        // If it's a CREATE TABLE statement, try to parse table name
        if (preg_match('/create\s+table\s+(if\s+not\s+exists\s+)?[`\"]?([a-zA-Z0-9_]+)[`\"]?/i', $stmt, $m)) {
            $table = $m[2];
            // Check if table exists in current database
            $check = $pdo->prepare("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $check->execute([$table]);
            $exists = $check->fetchColumn() > 0;
            if ($exists) {
                // Nothing to do for existing table (CREATE IF NOT EXISTS will not change schema)
                $results[] = ['table'=>$table, 'action'=>'exists'];
                continue;
            } else {
                $pdo->exec($stmt);
                $results[] = ['table'=>$table, 'action'=>'created'];
                continue;
            }
        }

        // For ALTER TABLE statements, try to extract table and execute
        if (preg_match('/alter\s+table\s+[`\"]?([a-zA-Z0-9_]+)[`\"]?/i', $stmt, $m2)) {
            $table = $m2[1];
            $pdo->exec($stmt);
            $results[] = ['table'=>$table, 'action'=>'altered'];
            continue;
        }

        // For INSERT IGNORE or other statements, attempt to execute
        $pdo->exec($stmt);
        $results[] = ['statement'=>substr($stmt,0,60).'...', 'action'=>'executed'];

    } catch (PDOException $e) {
        $results[] = ['statement'=>substr($stmt,0,60).'...', 'action'=>'error', 'error'=>$e->getMessage()];
    }
}

echo json_encode(['success'=>true,'results'=>$results]);
?>
