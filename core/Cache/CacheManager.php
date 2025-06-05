<?php

namespace Portfolion\Cache;

use Portfolion\Config;
use Portfolion\Cache\Store\FileStore;
use Portfolion\Cache\Store\RedisStore;
use Portfolion\Cache\Store\MemcachedStore;
use Portfolion\Cache\Store\StoreInterface;
use RuntimeException;

/**
 * Cache Manager for the Portfolion framework
 * 
 * This class manages different cache drivers and provides a unified interface
 * for interacting with the cache system.
 */
class CacheManager
{
    /**
     * @var array Available cache drivers
     */
    protected array $drivers = [];
    
    /**
     * @var array Cache store instances
     */
    protected array $stores = [];
    
    /**
     * @var Config Configuration instance
     */
    protected Config $config;
    
    /**
     * @var string Default cache driver
     */
    protected string $defaultDriver;
    
    /**
     * Create a new cache manager instance
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->defaultDriver = $this->config->get('cache.default', 'file');
        
        // Register default drivers
        $this->registerDefaultDrivers();
    }
    
    /**
     * Register the default cache drivers
     * 
     * @return void
     */
    protected function registerDefaultDrivers(): void
    {
        $this->drivers = [
            'file' => function ($config) {
                return new FileStore($config);
            },
            'redis' => function ($config) {
                return new RedisStore($config);
            },
            'memcached' => function ($config) {
                return new MemcachedStore($config);
            },
        ];
    }
    
    /**
     * Get a cache store instance
     * 
     * @param string|null $driver The driver name (null for default)
     * @return StoreInterface The cache store
     * @throws RuntimeException If the driver is not supported
     */
    public function store(?string $driver = null): StoreInterface
    {
        $driver = $driver ?? $this->defaultDriver;
        
        // Return existing store if already created
        if (isset($this->stores[$driver])) {
            return $this->stores[$driver];
        }
        
        // Create a new store
        $store = $this->createStore($driver);
        
        // Store the store instance
        $this->stores[$driver] = $store;
        
        return $store;
    }
    
    /**
     * Create a cache store for the specified driver
     * 
     * @param string $driver The driver name
     * @return StoreInterface The cache store
     * @throws RuntimeException If the driver is not supported
     */
    protected function createStore(string $driver): StoreInterface
    {
        // Check if the driver is supported
        if (!isset($this->drivers[$driver])) {
            throw new RuntimeException("Cache driver [{$driver}] is not supported.");
        }
        
        // Get the driver configuration
        $config = $this->getDriverConfig($driver);
        
        // Create the store
        return call_user_func($this->drivers[$driver], $config);
    }
    
    /**
     * Get the configuration for a driver
     * 
     * @param string $driver The driver name
     * @return array The driver configuration
     */
    protected function getDriverConfig(string $driver): array
    {
        return $this->config->get("cache.stores.{$driver}", []);
    }
    
    /**
     * Register a custom cache driver
     * 
     * @param string $driver The driver name
     * @param callable $callback The callback to create the driver
     * @return void
     */
    public function registerDriver(string $driver, callable $callback): void
    {
        $this->drivers[$driver] = $callback;
    }
    
    /**
     * Get the default cache driver name
     * 
     * @return string The default driver name
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }
    
    /**
     * Set the default cache driver name
     * 
     * @param string $driver The default driver name
     * @return void
     */
    public function setDefaultDriver(string $driver): void
    {
        $this->defaultDriver = $driver;
    }
    
    /**
     * Dynamically call the default store instance
     * 
     * @param string $method The method to call
     * @param array $parameters The parameters to pass
     * @return mixed The method result
     */
    public function __call(string $method, array $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
} 