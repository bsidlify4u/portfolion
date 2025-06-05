<?php

namespace Portfolion\Cache\Drivers;

use InvalidArgumentException;
use RuntimeException;

/**
 * File-based cache driver
 */
class FileDriver extends AbstractDriver
{
    /**
     * The cache directory.
     *
     * @var string
     */
    protected string $directory;

    /**
     * Create a new file cache driver instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        $this->directory = $config['path'] ?? storage_path('framework/cache');
        
        $this->ensureCacheDirectoryExists();
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        
        // Check if the file exists
        if (!file_exists($file)) {
            return $default;
        }
        
        // Get the contents of the cache file
        $contents = file_get_contents($file);
        if ($contents === false) {
            return $default;
        }
        
        // Decode the cache data
        $data = $this->decode($contents);
        
        // Check if the cache has expired
        if ($data['expiration'] !== 0 && time() > $data['expiration']) {
            $this->forget($key);
            return $default;
        }
        
        return $data['value'];
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        
        $expiration = $ttl > 0 ? time() + $ttl : 0;
        
        $data = $this->encode([
            'value' => $value,
            'expiration' => $expiration,
        ]);
        
        $file = $this->getFilePath($key);
        
        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return false;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool
    {
        $files = new \FilesystemIterator($this->directory);
        $success = true;
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                if (!@unlink($file->getPathname())) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        $current = $this->get($key, 0);
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = $current + $value;
        
        if ($this->put($key, $new)) {
            return $new;
        }
        
        return false;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    /**
     * Get the remaining time to live of a key that has a timeout.
     *
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key): ?int
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $contents = file_get_contents($file);
        if ($contents === false) {
            return null;
        }
        
        $data = $this->decode($contents);
        
        // If the item doesn't expire
        if ($data['expiration'] === 0) {
            return 0;
        }
        
        // If the item has already expired
        if (time() > $data['expiration']) {
            $this->forget($key);
            return null;
        }
        
        return $data['expiration'] - time();
    }

    /**
     * Get the full path for the given cache key.
     *
     * @param string $key
     * @return string
     */
    protected function getFilePath(string $key): string
    {
        $key = $this->prefixKey($key);
        $hash = md5($key);
        
        return $this->directory . '/' . $hash . '.cache';
    }

    /**
     * Ensure the cache directory exists and is writable.
     *
     * @return void
     * 
     * @throws RuntimeException
     */
    protected function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->directory)) {
            if (!mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
                throw new RuntimeException("Cache directory [{$this->directory}] could not be created.");
            }
        }
        
        if (!is_writable($this->directory)) {
            throw new RuntimeException("Cache directory [{$this->directory}] is not writable.");
        }
    }

    /**
     * Encode the cache data for storage.
     *
     * @param array $data
     * @return string
     */
    protected function encode(array $data): string
    {
        return serialize($data);
    }

    /**
     * Decode the cached data.
     *
     * @param string $data
     * @return array
     */
    protected function decode(string $data): array
    {
        $decoded = @unserialize($data);
        
        if ($decoded === false || !is_array($decoded) || 
            !isset($decoded['value']) || !isset($decoded['expiration'])) {
            return [
                'value' => null,
                'expiration' => 0,
            ];
        }
        
        return $decoded;
    }
} 