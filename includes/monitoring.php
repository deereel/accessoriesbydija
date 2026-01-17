<?php
class PerformanceMonitor {
    private static $instance = null;
    private $metrics = [];
    private $timers = [];

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Start timing an operation
    public function startTimer($operation, $context = []) {
        $this->timers[$operation] = [
            'start' => microtime(true),
            'context' => $context
        ];
    }

    // End timing and log performance
    public function endTimer($operation, $additionalContext = []) {
        if (!isset($this->timers[$operation])) {
            AppLogger::warning("Timer not started for operation: $operation");
            return;
        }

        $startTime = $this->timers[$operation]['start'];
        $endTime = microtime(true);
        $context = array_merge($this->timers[$operation]['context'], $additionalContext);

        AppLogger::logPerformance($operation, $startTime, $endTime, $context);

        unset($this->timers[$operation]);
    }

    // Record a metric
    public function recordMetric($name, $value, $unit = '', $context = []) {
        $this->metrics[] = [
            'name' => $name,
            'value' => $value,
            'unit' => $unit,
            'timestamp' => time(),
            'context' => $context
        ];

        // Log significant metrics
        if ($this->isSignificantMetric($name, $value)) {
            AppLogger::info("Metric recorded: $name = $value" . ($unit ? " $unit" : ""), $context);
        }
    }

    // Check if a metric is significant enough to log
    private function isSignificantMetric($name, $value) {
        $thresholds = [
            'db_query_time' => 0.1, // Log DB queries > 100ms
            'page_load_time' => 2.0, // Log page loads > 2 seconds
            'memory_usage' => 50 * 1024 * 1024, // Log memory > 50MB
            'error_count' => 1, // Log any errors
        ];

        return isset($thresholds[$name]) && $value >= $thresholds[$name];
    }

    // Monitor database query performance
    public function monitorDBQuery($query, $params = [], $executionTime = null) {
        if ($executionTime !== null) {
            $this->recordMetric('db_query_time', $executionTime, 'seconds', [
                'query' => substr($query, 0, 100) . (strlen($query) > 100 ? '...' : ''),
                'param_count' => count($params)
            ]);
        }
    }

    // Monitor page load performance
    public function monitorPageLoad($page, $loadTime) {
        $this->recordMetric('page_load_time', $loadTime, 'seconds', [
            'page' => $page,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }

    // Monitor memory usage
    public function monitorMemoryUsage($context = '') {
        $usage = memory_get_peak_usage(true);
        $this->recordMetric('memory_usage', $usage, 'bytes', [
            'context' => $context,
            'real_usage' => true
        ]);
    }

    // Get current metrics summary
    public function getMetricsSummary() {
        $summary = [
            'total_metrics' => count($this->metrics),
            'active_timers' => count($this->timers),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        ];

        return $summary;
    }

    // Log metrics summary at end of request
    public function logRequestSummary() {
        $summary = $this->getMetricsSummary();

        AppLogger::info('Request completed', [
            'metrics_count' => $summary['total_metrics'],
            'active_timers' => $summary['active_timers'],
            'memory_peak_mb' => round($summary['memory_peak'] / 1024 / 1024, 2),
            'execution_time_ms' => round($summary['execution_time'] * 1000, 2),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ]);
    }
}

// Global performance monitoring functions
function start_performance_timer($operation, $context = []) {
    PerformanceMonitor::getInstance()->startTimer($operation, $context);
}

function end_performance_timer($operation, $additionalContext = []) {
    PerformanceMonitor::getInstance()->endTimer($operation, $additionalContext);
}

function record_metric($name, $value, $unit = '', $context = []) {
    PerformanceMonitor::getInstance()->recordMetric($name, $value, $unit, $context);
}

// Database query monitoring wrapper
function monitored_db_query($pdo, $query, $params = []) {
    $startTime = microtime(true);

    try {
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute($params);

        $executionTime = microtime(true) - $startTime;
        PerformanceMonitor::getInstance()->monitorDBQuery($query, $params, $executionTime);

        return [$stmt, $result];
    } catch (PDOException $e) {
        $executionTime = microtime(true) - $startTime;
        PerformanceMonitor::getInstance()->monitorDBQuery($query, $params, $executionTime);

        throw $e;
    }
}

// Auto-log request summary at shutdown
register_shutdown_function(function() {
    PerformanceMonitor::getInstance()->logRequestSummary();
});
?>