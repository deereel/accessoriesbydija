<?php
// Health check endpoint for monitoring
require_once '../includes/init.php';

header('Content-Type: application/json');

// Start performance monitoring for this request
start_performance_timer('health_check');

try {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'checks' => []
    ];

    // Database connectivity check
    $dbStart = microtime(true);
    try {
        $stmt = $pdo->query("SELECT 1");
        $stmt->fetch();
        $dbTime = microtime(true) - $dbStart;
        $health['checks']['database'] = [
            'status' => 'healthy',
            'response_time' => round($dbTime * 1000, 2) . 'ms'
        ];
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'status' => 'unhealthy',
            'error' => 'Database connection failed'
        ];
        $health['status'] = 'unhealthy';
        AppLogger::error('Health check: Database unhealthy', ['error' => $e->getMessage()]);
    }

    // File system check
    $logDir = __DIR__ . '/../logs';
    if (is_writable($logDir)) {
        $health['checks']['filesystem'] = [
            'status' => 'healthy',
            'writable_dirs' => ['logs']
        ];
    } else {
        $health['checks']['filesystem'] = [
            'status' => 'unhealthy',
            'error' => 'Logs directory not writable'
        ];
        $health['status'] = 'unhealthy';
    }

    // Memory usage
    $memoryUsage = memory_get_peak_usage(true);
    $memoryLimit = ini_get('memory_limit');
    $health['checks']['memory'] = [
        'status' => 'healthy',
        'peak_usage' => round($memoryUsage / 1024 / 1024, 2) . 'MB',
        'limit' => $memoryLimit
    ];

    // PHP version
    $health['checks']['php'] = [
        'status' => 'healthy',
        'version' => PHP_VERSION
    ];

    // Environment
    $health['environment'] = $environment ?? 'unknown';

    // Response time
    $responseTime = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    $health['response_time_ms'] = round($responseTime, 2);

    // Log health check result
    if ($health['status'] === 'unhealthy') {
        AppLogger::warning('Health check failed', ['checks' => $health['checks']]);
    } else {
        AppLogger::debug('Health check passed', ['response_time_ms' => $responseTime]);
    }

    // Set HTTP status code
    http_response_code($health['status'] === 'healthy' ? 200 : 503);

    end_performance_timer('health_check');

    echo json_encode($health, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    AppLogger::error('Health check exception', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Health check failed',
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>