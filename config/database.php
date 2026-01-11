<?php
// Auto-detect environment based on domain
$host_name = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($host_name, 'dev.') === 0) {
    $environment = 'dev';
} elseif ($host_name === 'localhost' || strpos($host_name, '127.0.0.1') === 0) {
    $environment = 'local';
} else {
    $environment = 'live';
}

switch ($environment) {
    case 'local':
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'dija_accessories';
        $BASE_URL = 'http://localhost/accessoriesbydija';
        break;

    case 'dev':
        $host = 'localhost';
        $username = 'u909899644_oladayo';
        $password = '3~jlSW~oH';
        $database = 'u909899644_test_accessory';
        $BASE_URL = 'https://dev.accessoriesbydija.uk';
        break;

    case 'live':
        $host = 'localhost';
        $username = 'u909899644_adjquadri';
        $password = '6;fZApMBW!';
        $database = 'u909899644_dija_accessory';
        $BASE_URL = 'https://accessoriesbydija.uk';
        break;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>