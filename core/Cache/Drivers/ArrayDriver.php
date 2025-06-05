<?php

namespace Portfolion\Cache\Drivers;

/**
 * In-memory array-based cache driver
 */
class ArrayDriver extends AbstractDriver
{
    /**
     * The array of stored values.
     *
     * @var array
     */
    protected array $storage = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefixKey($key);
        
        if (!isset($this->storage[$key])) {
            return $default;
        }
        
        $item = $this->storage[$key];
        
        // Check if the item has expired
        if ($item['expiration'] !== 0 && time() > $item['expiration']) {
            $this->forget($key);
            return $default;
        }
        
        return $item['value'];
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
        $key = $this->prefixKey($key);
        $ttl = $this->normalizeTtl($ttl);
        
        $expiration = $ttl > 0 ? time() + $ttl : 0;
        
        $this->storage[$key] = [
            'value' => $value,
            'expiration' => $expiration,
        ];
        
        return true;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $key = $this->prefixKey($key);
        
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
            return true;
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
        $this->storage = [];
        
        return true;
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
        $prefixedKey = $this->prefixKey($key);
        $current = $this->get($key, 0);
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = $current + $value;
        
        $this->put($key, $new);
        
        return $new;
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
        $key = $this->prefixKey($key);
        
        if (!isset($this->storage[$key])) {
            return null;
        }
        
        $item = $this->storage[$key];
        
        // If the item doesn't expire
        if ($item['expiration'] === 0) {
            return 0;
        }
        
        // If the item has already expired
        if (time() > $item['expiration']) {
            $this->forget($key);
            return null;
        }
        
        return $item['expiration'] - time();
    }
} 