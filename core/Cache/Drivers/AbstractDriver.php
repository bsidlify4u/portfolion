<?php

namespace Portfolion\Cache\Drivers;

/**
 * Abstract base class for cache drivers
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * The cache configuration.
     *
     * @var array
     */
    protected array $config;

    /**
     * The default cache TTL in seconds.
     *
     * @var int
     */
    protected int $defaultTtl;

    /**
     * Create a new cache driver instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultTtl = $config['ttl'] ?? 3600; // 1 hour default
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key
     * @param int|null $ttl
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(string $key, ?int $ttl, \Closure $callback): mixed
    {
        $value = $this->get($key);

        // Return cached value if exists
        if ($value !== null) {
            return $value;
        }

        // Execute the callback to get the value
        $value = $callback();

        // Store the value in the cache
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param \Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, \Closure $callback): mixed
    {
        return $this->remember($key, 0, $callback);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    protected function getPrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    /**
     * Get the prefixed key.
     *
     * @param string $key
     * @return string
     */
    protected function prefixKey(string $key): string
    {
        $prefix = $this->getPrefix();
        
        return $prefix ? "{$prefix}:{$key}" : $key;
    }

    /**
     * Normalize the TTL value.
     *
     * @param int|null $ttl
     * @return int
     */
    protected function normalizeTtl(?int $ttl): int
    {
        // If TTL is null, use the default TTL
        if ($ttl === null) {
            return $this->defaultTtl;
        }

        // If TTL is 0 or negative, the item should be stored indefinitely
        if ($ttl <= 0) {
            return 0;
        }

        return $ttl;
    }

    /**
     * Serialize the value for storage.
     *
     * @param mixed $value
     * @return string
     */
    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * Unserialize the stored value.
     *
     * @param string $value
     * @return mixed
     */
    protected function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
} 