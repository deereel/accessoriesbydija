<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../includes/logger.php';

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'dija_accessories';
$BASE_URL = getenv('APP_URL') ?: 'http://localhost';

AppLogger::debug("DB config: host=$host, user=$username, db=$database, pass=" . (empty($password) ? 'empty' : 'set (' . strlen($password) . ' chars)'));

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    AppLogger::critical("DB connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?>