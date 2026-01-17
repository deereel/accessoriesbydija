<?php
/**
 * Environment Configuration Loader
 * Loads variables from .env.{environment} file into PHP environment
 */

require_once __DIR__ . '/environment.php';

$env_file = __DIR__ . '/../.env.' . $environment;
error_log("Env file path: $env_file");

if (file_exists($env_file)) {
    error_log("Env file exists: yes");
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (in_array($value[0] ?? null, ['"', "'"])) {
                $value = substr($value, 1, -1);
            }

            // Set environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            error_log("Loaded env var: $key" . (preg_match('/(password|secret|key)/i', $key) ? ' (sensitive)' : " = '$value'"));
        }
    }
    error_log("Env loading completed for environment: $environment");
} else {
    error_log("Env file does not exist for environment: $environment");
}
