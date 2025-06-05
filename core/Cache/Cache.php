<?php
namespace Portfolion\Cache;

use Portfolion\Config;
use Portfolion\Cache\Drivers\DriverInterface;
use Portfolion\Cache\Drivers\FileDriver;
use Portfolion\Cache\Drivers\MemcachedDriver;
use Portfolion\Cache\Drivers\RedisDriver;
use Portfolion\Cache\Drivers\ArrayDriver;
use Portfolion\Cache\Drivers\NullDriver;
use InvalidArgumentException;

/**
 * Cache manager for handling different cache stores
 */
class Cache
{
    /**
     * The application instance.
     *
     * @var \Portfolion\Application
     */
    protected $app;

    /**
     * The cache store implementations.
     *
     * @var array<string, DriverInterface>
     */
    protected $stores = [];

    /**
     * The cache driver configurations.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new Cache manager instance.
     *
     * @param \Portfolion\Application|null $app
     */
    public function __construct($app = null)
    {
        $this->app = $app ?? app();
        $this->config = Config::getInstance()->get('cache', []);
    }

    /**
     * Get a cache store instance by name.
     *
     * @param string|null $name
     * @return DriverInterface
     */
    public function store(?string $name = null): DriverInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] = $this->stores[$name] ?? $this->resolve($name);
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config['default'] ?? 'file';
    }

    /**
     * Resolve the given cache store.
     *
     * @param string $name
     * @return DriverInterface
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): DriverInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? '';

        return match ($driver) {
            'file' => new FileDriver($config),
            'memcached' => new MemcachedDriver($config),
            'redis' => new RedisDriver($config),
            'array' => new ArrayDriver(),
            'null' => new NullDriver(),
            default => throw new InvalidArgumentException("Driver [{$driver}] is not supported."),
        };
    }

    /**
     * Get the cache configuration.
     *
     * @param string $name
     * @return array|null
     */
    protected function getConfig(string $name): ?array
    {
        return $this->config['stores'][$name] ?? null;
    }

    /**
     * Get an item from the cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->store()->put($key, $value, $ttl);
    }

    /**
     * Store an item in the cache forever.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->store()->forever($key, $value);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return $this->store()->forget($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool
    {
        return $this->store()->flush();
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param string $key
     * @param int|null $ttl
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(string $key, ?int $ttl, \Closure $callback): mixed
    {
        return $this->store()->remember($key, $ttl, $callback);
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param string $key
     * @param \Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, \Closure $callback): mixed
    {
        return $this->store()->rememberForever($key, $callback);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->store()->has($key);
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
        return $this->store()->increment($key, $value);
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
        return $this->store()->decrement($key, $value);
    }

    /**
     * Get the remaining time to live of a key that has a timeout.
     *
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key): ?int
    {
        return $this->store()->ttl($key);
    }

    /**
     * Dynamically pass methods to the default store.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->store()->$method(...$parameters);
    }
}
