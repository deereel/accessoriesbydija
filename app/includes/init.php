<?php
// Application initialization
// This file should be included at the top of all PHP files

// Define absolute paths to avoid relative path issues
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
define('APP_PATH', realpath(__DIR__ . '/..'));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment configuration
require_once APP_PATH . '/config/environment.php';

// Load logger (includes error handlers)
require_once __DIR__ . '/logger.php';

// Load monitoring
require_once __DIR__ . '/monitoring.php';

// Load caching
require_once __DIR__ . '/cache.php';

// Load database configuration
require_once APP_PATH . '/config/database.php';

// Log application start
AppLogger::debug('Application initialized', [
    'environment' => $environment ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
]);
?>