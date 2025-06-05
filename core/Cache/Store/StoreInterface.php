<?php
namespace Portfolion\Cache\Store;

/**
 * Cache store interface providing methods for basic cache operations.
 */
interface StoreInterface {
    /**
     * Get an item from the cache.
     *
     * @template T
     * @param string $key
     * @param T $default
     * @return T
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds. 0 means no expiration.
     * @return bool True on success, false on failure
     */
    public function put(string $key, mixed $value, int $ttl = 0): bool;
    
    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return bool True on success, false on failure
     */
    public function forever(string $key, mixed $value): bool;
    
    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool True if the item was removed, false otherwise
     */
    public function forget(string $key): bool;
    
    /**
     * Remove all items from the cache.
     *
     * @return bool True on success, false on failure
     */
    public function flush(): bool;
    
    /**
     * Increment a value in the cache.
     *
     * @param string $key
     * @param int $value Amount to increment by
     * @return int|false The new value on success, false on failure
     */
    public function increment(string $key, int $value = 1): int|false;
    
    /**
     * Decrement a value in the cache.
     *
     * @param string $key
     * @param int $value Amount to decrement by
     * @return int|false The new value on success, false on failure
     */
    public function decrement(string $key, int $value = 1): int|false;
    
    /**
     * Check if an item exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Get the remaining time to live of a key that has a timeout.
     *
     * @param string $key
     * @return int|null TTL in seconds, null when key does not exist, -1 when key exists but has no TTL
     */
    public function ttl(string $key): ?int;
}
