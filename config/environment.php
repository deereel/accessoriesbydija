<?php
// Auto-detect environment based on domain
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
error_log("Environment detection: HTTP_HOST = '$host'");

if ($host === 'dev.accessoriesbydija.uk') {
    $environment = 'dev';
} elseif ($host === 'accessoriesbydija.uk') {
    $environment = 'live';
} elseif (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $environment = 'local';
} else {
    $environment = 'local'; // Default to local for unknown domains
}
error_log("Environment detected: '$environment'");
?>