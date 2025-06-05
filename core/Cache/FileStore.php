<?php
namespace Portfolion\Cache;

class FileStore implements StoreInterface {
    protected $directory;
    protected $config;
    
    public function __construct($config) {
        $this->config = $config;
        $this->directory = $config->get('cache.file.path', storage_path('framework/cache'));
        
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
    }
    
    public function get(string $key) {
        $path = $this->path($key);
        
        if (!$this->has($key)) {
            return null;
        }
        
        $contents = file_get_contents($path);
        $expire = substr($contents, 0, 10);
        
        if (time() >= $expire) {
            $this->forget($key);
            return null;
        }
        
        return substr($contents, 10);
    }
    
    public function put(string $key, $value, int $ttl = 3600): bool {
        $path = $this->path($key);
        $expire = $ttl > 0 ? time() + $ttl : PHP_INT_MAX;
        
        try {
            file_put_contents(
                $path,
                $expire . $value,
                LOCK_EX
            );
            
            chmod($path, 0640);
        } catch (\Exception $e) {
            return false;
        }
        
        return true;
    }
    
    public function has(string $key): bool {
        return file_exists($this->path($key));
    }
    
    public function forget(string $key): bool {
        if ($this->has($key)) {
            return @unlink($this->path($key));
        }
        
        return false;
    }
    
    public function forever(string $key, $value): bool {
        return $this->put($key, $value, 0);
    }
    
    public function increment(string $key, int $value = 1): int {
        $current = (int) $this->get($key);
        $new = $current + $value;
        
        if ($this->put($key, $new)) {
            return $new;
        }
        
        return $current;
    }
    
    public function decrement(string $key, int $value = 1): int {
        return $this->increment($key, -$value);
    }
    
    public function clear(): bool {
        $files = glob($this->directory . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        return true;
    }
    
    protected function path(string $key): string {
        $hash = sha1($key);
        return $this->directory . '/' . $hash;
    }
}
