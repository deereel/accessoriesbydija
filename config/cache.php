<?php
// Simple File-based Caching System

class SimpleCache {
    private $cache_dir;
    private $default_ttl = 3600; // 1 hour
    
    public function __construct($cache_dir = null) {
        $this->cache_dir = $cache_dir ?: sys_get_temp_dir() . '/dija_cache/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?: $this->default_ttl;
        $file = $this->cache_dir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public function delete($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        return file_exists($file) ? unlink($file) : true;
    }
    
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
}

// Initialize cache instance
$cache = new SimpleCache();

// Cache helper functions
function getCachedData($key) {
    global $cache;
    return $cache->get($key);
}

function setCachedData($key, $data, $ttl = 3600) {
    global $cache;
    return $cache->set($key, $data, $ttl);
}
?>