<?php
// Auto-detect environment based on domain
$host = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0];
error_log("Environment detection: HTTP_HOST = '$host'");

if (strpos($host, 'dev.accessoriesbydija.uk') !== false) {
    $environment = 'dev';
} elseif (strpos($host, 'accessoriesbydija.uk') !== false && strpos($host, 'dev.') === false) {
    $environment = 'live';
} elseif (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $environment = 'local';
} else {
    $environment = 'local'; // Default to local for unknown domains
}
error_log("Environment detected: '$environment'");
?>