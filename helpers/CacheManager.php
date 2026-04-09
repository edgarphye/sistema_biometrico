<?php

/**
 * Cache Manager - Sistema de caché centralizado con soporte Redis y fallback a archivos
 */

class CacheManager {
    private static $instance = null;
    private static $redis = null;
    private static $useRedis = false;
    private static $cacheDir = __DIR__ . '/../cache/';
    private static $prefix = 'biometrico_';
    
    /**
     * Constructor - Singleton pattern
     */
    public function __construct() {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        // Try Redis connection
        try {
            if (class_exists('Redis')) {
                self::$redis = new Redis();
                self::$redis->connect('127.0.0.1', 6379, 2);
                self::$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
                self::$useRedis = true;
            } else {
                self::$useRedis = false;
            }
        } catch (Exception $e) {
            error_log("Redis not available, using file cache: " . $e->getMessage());
            self::$useRedis = false;
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $ttl = 3600) {
        $fullKey = self::$prefix . $key;
        
        if (self::$useRedis && self::$redis) {
            try {
                return self::$redis->setex($fullKey, $ttl, $value);
            } catch (Exception $e) {
                error_log("Redis set failed: " . $e->getMessage());
                self::$useRedis = false;
            }
        }
        
        // Fallback to file cache
        return $this->setFile($fullKey, $value, $ttl);
    }
    
    /**
     * Get cache value
     */
    public function get($key, $default = null) {
        $fullKey = self::$prefix . $key;
        
        if (self::$useRedis && self::$redis) {
            try {
                $value = self::$redis->get($fullKey);
                if ($value !== false) {
                    return $value;
                }
            } catch (Exception $e) {
                error_log("Redis get failed: " . $e->getMessage());
                self::$useRedis = false;
            }
        }
        
        // Fallback to file cache
        return $this->getFile($fullKey, $default);
    }
    
    /**
     * Delete cache value
     */
    public function delete($key) {
        $fullKey = self::$prefix . $key;
        
        if (self::$useRedis && self::$redis) {
            try {
                self::$redis->del($fullKey);
            } catch (Exception $e) {
                error_log("Redis delete failed: " . $e->getMessage());
                self::$useRedis = false;
            }
        }
        
        // Delete file cache
        $file = self::$cacheDir . md5($fullKey) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * Check if key exists
     */
    public function has($key) {
        $fullKey = self::$prefix . $key;
        
        if (self::$useRedis && self::$redis) {
            try {
                return self::$redis->exists($fullKey);
            } catch (Exception $e) {
                error_log("Redis exists failed: " . $e->getMessage());
                self::$useRedis = false;
            }
        }
        
        // Check file cache
        $file = self::$cacheDir . md5($fullKey) . '.cache';
        if (!file_exists($file)) {
            return false;
        }
        
        // Check if expired
        $data = unserialize(file_get_contents($file));
        return $data['expires'] > time();
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        if (self::$useRedis && self::$redis) {
            try {
                self::$redis->flushDB();
            } catch (Exception $e) {
                error_log("Redis clear failed: " . $e->getMessage());
                self::$useRedis = false;
            }
        }
        
        // Clear file cache
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Set file cache
     */
    private function setFile($key, $value, $ttl) {
        $file = self::$cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    /**
     * Get file cache
     */
    private function getFile($key, $default = null) {
        $file = self::$cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] <= time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Increment numeric value
     */
    public function increment($key, $step = 1) {
        $value = $this->get($key, 0);
        $value += $step;
        $this->set($key, $value);
        return $value;
    }
    
    /**
     * Decrement numeric value
     */
    public function decrement($key, $step = 1) {
        $value = $this->get($key, 0);
        $value -= $step;
        $this->set($key, $value);
        return $value;
    }
}