<?php
namespace Portfolion\Cache\Store;

use RuntimeException;

/**
 * File-based cache store implementation.
 */
class FileStore implements StoreInterface {
    private string $directory;
    private const FILE_EXTENSION = '.cache';
    private const LOCK_TIMEOUT = 1000; // milliseconds
    
    /**
     * @param array{path: string} $config
     * @throws RuntimeException If the cache directory cannot be created or is not writable
     */
    public function __construct(array $config) {
        $this->directory = rtrim($config['path'], '/');
        
        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true)) {
            throw new RuntimeException("Could not create directory {$this->directory}");
        }
        
        if (!is_writable($this->directory)) {
            throw new RuntimeException("Directory {$this->directory} is not writable");
        }
    }
    
    public function get(string $key, mixed $default = null): mixed {
        $path = $this->path($key);
        
        if (!is_file($path)) {
            return $default;
        }
        
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return $default;
        }
        
        try {
            if (!flock($handle, LOCK_SH)) {
                return $default;
            }
            
            $contents = stream_get_contents($handle);
            if ($contents === false) {
                return $default;
            }
            
            $data = @unserialize($contents);
            if (!is_array($data) || !isset($data['expiration'], $data['value'])) {
                return $default;
            }
            
            if ($data['expiration'] !== 0 && time() >= $data['expiration']) {
                $this->forget($key);
                return $default;
            }
            
            return $data['value'];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
    
    public function put(string $key, mixed $value, int $ttl = 0): bool {
        $path = $this->path($key);
        $expiration = $ttl > 0 ? time() + $ttl : 0;
        
        $tempPath = $path . '.tmp';
        $handle = fopen($tempPath, 'w');
        if ($handle === false) {
            return false;
        }
        
        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }
            
            $success = fwrite($handle, serialize([
                'expiration' => $expiration,
                'value' => $value
            ])) !== false;
            
            fflush($handle);
            
            if (!$success || !rename($tempPath, $path)) {
                unlink($tempPath);
                return false;
            }
            
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
    
    public function forever(string $key, mixed $value): bool {
        return $this->put($key, $value, 0);
    }
    
    public function forget(string $key): bool {
        $path = $this->path($key);
        return !file_exists($path) || @unlink($path);
    }
    
    public function flush(): bool {
        $pattern = $this->directory . '/*' . self::FILE_EXTENSION;
        $files = glob($pattern);
        
        if ($files === false) {
            return false;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file) && !@unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    public function increment(string $key, int $value = 1): int|false {
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = $current + $value;
        return $this->put($key, $new) ? $new : false;
    }
    
    public function decrement(string $key, int $value = 1): int|false {
        return $this->increment($key, -$value);
    }
    
    public function has(string $key): bool {
        return $this->get($key, null) !== null;
    }
    
    public function ttl(string $key): ?int {
        $path = $this->path($key);
        
        if (!is_file($path)) {
            return null;
        }
        
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }
        
        try {
            if (!flock($handle, LOCK_SH)) {
                return null;
            }
            
            $contents = stream_get_contents($handle);
            if ($contents === false) {
                return null;
            }
            
            $data = @unserialize($contents);
            if (!is_array($data) || !isset($data['expiration'])) {
                return null;
            }
            
            if ($data['expiration'] === 0) {
                return -1;
            }
            
            $ttl = $data['expiration'] - time();
            return $ttl > 0 ? $ttl : null;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
    
    /**
     * Get the full path for a cache key.
     */
    private function path(string $key): string {
        return $this->directory . '/' . md5($key) . self::FILE_EXTENSION;
    }
}
