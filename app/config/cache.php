<?php
/**
 * Caching Configuration
 * Supports Redis and Memcached
 */

// Check if Redis is available
if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379); // Adjust host/port as needed
        $cache_type = 'redis';
    } catch (Exception $e) {
        $cache_type = 'file'; // Fallback if Redis connection fails
    }
} elseif (class_exists('Memcached')) {
    try {
        $memcached = new Memcached();
        $memcached->addServer('127.0.0.1', 11211); // Adjust host/port as needed
        $cache_type = 'memcached';
    } catch (Exception $e) {
        $cache_type = 'file'; // Fallback if Memcached connection fails
    }
} else {
    $cache_type = 'file'; // Fallback to file-based cache
}

/**
 * Set cache value
 */
function cache_set($key, $value, $ttl = 3600) {
    global $redis, $memcached, $cache_type;

    if ($cache_type === 'redis') {
        return $redis->setex($key, $ttl, serialize($value));
    } elseif ($cache_type === 'memcached') {
        return $memcached->set($key, $value, $ttl);
    } else {
        // File-based cache
        $cache_dir = __DIR__ . '/../../cache/';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        $file = $cache_dir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        return file_put_contents($file, serialize($data)) !== false;
    }
}

/**
 * Get cache value
 */
function cache_get($key) {
    global $redis, $memcached, $cache_type;

    if ($cache_type === 'redis') {
        $data = $redis->get($key);
        return $data ? unserialize($data) : false;
    } elseif ($cache_type === 'memcached') {
        return $memcached->get($key);
    } else {
        // File-based cache
        $cache_dir = __DIR__ . '/../../cache/';
        $file = $cache_dir . md5($key) . '.cache';
        if (!file_exists($file)) {
            return false;
        }
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return false;
        }
        return $data['value'];
    }
}

/**
 * Delete cache value
 */
function cache_delete($key) {
    global $redis, $memcached, $cache_type;

    if ($cache_type === 'redis') {
        return $redis->del($key);
    } elseif ($cache_type === 'memcached') {
        return $memcached->delete($key);
    } else {
        // File-based cache
        $cache_dir = __DIR__ . '/../../cache/';
        $file = $cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
}
?>