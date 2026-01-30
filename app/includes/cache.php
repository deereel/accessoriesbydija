<?php
class AppCache {
    private static $instance = null;
    private $cacheDir;
    private $ttl;

    private function __construct() {
        $this->cacheDir = __DIR__ . '/../../cache';
        $this->ttl = 3600; // 1 hour default TTL

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    public function get($key) {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));
        if (!$data || time() > $data['expires']) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->ttl;
        $file = $this->getCacheFile($key);

        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        file_put_contents($file, serialize($data));
    }

    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    // Cache database query results
    public function getQueryResult($pdo, $query, $params = [], $ttl = null) {
        $key = 'query_' . md5($query . serialize($params));
        $cached = $this->get($key);

        if ($cached !== null) {
            AppLogger::debug("Cache hit for query", ['query' => substr($query, 0, 50) . '...']);
            return $cached;
        }

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->set($key, $result, $ttl);
            AppLogger::debug("Cache miss for query, stored result", [
                'query' => substr($query, 0, 50) . '...',
                'result_count' => count($result)
            ]);

            return $result;
        } catch (Exception $e) {
            AppLogger::error("Cached query failed: " . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    // Cache expensive computations
    public function remember($key, callable $callback, $ttl = null) {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }
}
?>