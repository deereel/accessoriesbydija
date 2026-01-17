<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;

// Load environment configuration
require_once __DIR__ . '/../config/environment.php';

class AppLogger {
    private static $instance = null;
    private $logger;

    private function __construct() {
        global $environment;

        // Determine log level based on environment
        $logLevel = ($environment === 'live') ? Logger::WARNING : Logger::DEBUG;

        // Create logger
        $this->logger = new Logger('accessoriesbydija');

        // Add processors
        $this->logger->pushProcessor(new WebProcessor());
        $this->logger->pushProcessor(new MemoryUsageProcessor());
        $this->logger->pushProcessor(new MemoryPeakUsageProcessor());

        // Add handlers based on environment
        if ($environment === 'live') {
            // Production: Rotating file handler for errors and warnings
            $this->logger->pushHandler(new RotatingFileHandler(
                __DIR__ . '/../logs/app.log',
                30, // Keep 30 days of logs
                $logLevel
            ));

            // Also log to system error log for critical errors
            $this->logger->pushHandler(new StreamHandler('php://stderr', Logger::ERROR));
        } else {
            // Development: Single file with all levels
            $this->logger->pushHandler(new StreamHandler(
                __DIR__ . '/../logs/app_dev.log',
                $logLevel
            ));

            // Also keep the debug.log for compatibility
            $this->logger->pushHandler(new StreamHandler(
                __DIR__ . '/../debug.log',
                Logger::DEBUG
            ));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function debug($message, array $context = []) {
        self::getInstance()->logger->debug($message, $context);
    }

    public static function info($message, array $context = []) {
        self::getInstance()->logger->info($message, $context);
    }

    public static function notice($message, array $context = []) {
        self::getInstance()->logger->notice($message, $context);
    }

    public static function warning($message, array $context = []) {
        self::getInstance()->logger->warning($message, $context);
    }

    public static function error($message, array $context = []) {
        self::getInstance()->logger->error($message, $context);
    }

    public static function critical($message, array $context = []) {
        self::getInstance()->logger->critical($message, $context);
    }

    public static function alert($message, array $context = []) {
        self::getInstance()->logger->alert($message, $context);
    }

    public static function emergency($message, array $context = []) {
        self::getInstance()->logger->emergency($message, $context);
    }

    // Log exceptions with full context
    public static function logException(\Exception $e, $level = Logger::ERROR, array $extraContext = []) {
        $context = array_merge([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], $extraContext);

        self::getInstance()->logger->log($level, 'Exception: ' . $e->getMessage(), $context);
    }

    // Performance logging
    public static function logPerformance($operation, $startTime, $endTime = null, array $context = []) {
        if ($endTime === null) {
            $endTime = microtime(true);
        }

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $context = array_merge($context, [
            'operation' => $operation,
            'duration_ms' => round($duration, 2),
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        if ($duration > 1000) { // Log slow operations (>1 second)
            self::warning("Slow operation: {$operation} took {$duration}ms", $context);
        } else {
            self::debug("Performance: {$operation} took {$duration}ms", $context);
        }
    }
}

// Global error handler
function app_error_handler($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    $errorType = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'UNKNOWN';

    $context = [
        'error_type' => $errorType,
        'error_number' => $errno,
        'file' => $errfile,
        'line' => $errline
    ];

    // Log based on error level
    if ($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
        AppLogger::error("PHP Error: {$errstr}", $context);
    } elseif ($errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING)) {
        AppLogger::warning("PHP Warning: {$errstr}", $context);
    } elseif ($errno & (E_NOTICE | E_USER_NOTICE | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED)) {
        AppLogger::notice("PHP Notice: {$errstr}", $context);
    }

    // Don't execute PHP's internal error handler
    return true;
}

// Global exception handler
function app_exception_handler($exception) {
    AppLogger::logException($exception, Logger::ERROR);
}

// Set error handlers
set_error_handler('app_error_handler');
set_exception_handler('app_exception_handler');

// Ensure logs directory exists
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}
?>