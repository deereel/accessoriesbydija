<?php
// Simple file-based MemoryBank for short persistent memories
// Location: app/memory/memory.php

class MemoryBank {
    private $file;
    private $data = [];

    public function __construct($file = null)
    {
        $this->file = $file ?: __DIR__ . '/memory.json';
        if (file_exists($this->file)) {
            $json = file_get_contents($this->file);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $this->data = $decoded;
            }
        }
    }

    // Save a memory entry by key. $value can be any JSON-serializable data.
    public function save(string $key, $value, array $tags = []) : bool
    {
        $this->data[$key] = [
            'value' => $value,
            'tags' => array_values($tags),
            'updated' => time()
        ];
        return $this->persist();
    }

    // Retrieve a memory entry by key. Returns null if not found.
    public function get(string $key)
    {
        return $this->data[$key]['value'] ?? null;
    }

    // Retrieve raw entry (value + metadata)
    public function getEntry(string $key)
    {
        return $this->data[$key] ?? null;
    }

    // Delete an entry
    public function delete(string $key) : bool
    {
        if (!isset($this->data[$key])) return false;
        unset($this->data[$key]);
        return $this->persist();
    }

    // Search entries by tag, returns array of keys => entries
    public function searchByTag(string $tag) : array
    {
        $out = [];
        foreach ($this->data as $k => $entry) {
            if (!empty($entry['tags']) && in_array($tag, $entry['tags'], true)) {
                $out[$k] = $entry;
            }
        }
        return $out;
    }

    // List all keys
    public function listKeys() : array
    {
        return array_keys($this->data);
    }

    // Persist data to disk (atomic write)
    private function persist() : bool
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) return false;
        }
        $tmp = $this->file . '.tmp';
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
        // rename is atomic on most platforms
        if (!rename($tmp, $this->file)) {
            // fallback: try copy then unlink
            if (!copy($tmp, $this->file)) return false;
            @unlink($tmp);
        }
        return true;
    }
}

// Optional: simple procedural helpers
if (!function_exists('memory_bank')) {
    function memory_bank($file = null)
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new MemoryBank($file);
        }
        return $instance;
    }
}
