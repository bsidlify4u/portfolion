<?php

namespace Portfolion\Providers;

use Portfolion\App;
use Portfolion\Cache\CacheManager;
use Portfolion\Cache\Cache;

/**
 * Service provider for cache services
 * 
 * This provider registers cache-related services.
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * @var App Application instance
     */
    protected App $app;
    
    /**
     * Register the service provider
     * 
     * @param App $app Application instance
     * @return void
     */
    public function register(App $app): void
    {
        $this->app = $app;
        
        // Register cache manager
        $app->singleton(CacheManager::class, function () {
            return new CacheManager();
        });
        
        // Register cache facade
        $app->singleton(Cache::class, function ($app) {
            return Cache::getInstance();
        });
        
        // Register cache as a shared alias
        $app->alias('cache', Cache::class);
    }
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot(): void
    {
        // Nothing to boot
    }
} 