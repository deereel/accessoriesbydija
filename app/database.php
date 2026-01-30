<?php
// Database Update Script
// Access via: http://yourdomain.com/app/database.php

require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Database Update Script</h2>";
    echo "<pre>";

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    $pdo->exec("USE $database");
    echo "✓ Database '$database' ready\n";

    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/../database.sql');

    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $created = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        try {
            $pdo->exec($statement);

            // Check if it's a CREATE TABLE statement
            if (stripos($statement, 'CREATE TABLE') === 0) {
                preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                echo "✓ Table '{$matches[1]}' created/updated\n";
                $created++;
            }
            // Check if it's an INSERT statement
            elseif (stripos($statement, 'INSERT INTO') === 0) {
                preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches);
                echo "✓ Data inserted into '{$matches[1]}'\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                echo "- Table '{$matches[1]}' already exists\n";
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "- Duplicate data skipped\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }

    echo "\n=== Summary ===\n";
    echo "Tables processed: $created\n";
    echo "Errors: $errors\n";
    echo "Database update completed!\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>