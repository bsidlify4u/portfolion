<?php

namespace Portfolion\Providers;

use Portfolion\App;
use Portfolion\Queue\QueueManager;
use Portfolion\Queue\Worker;

/**
 * Service provider for queue services
 * 
 * This provider registers queue-related services.
 */
class QueueServiceProvider implements ServiceProviderInterface
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
        
        // Register queue manager
        $app->singleton(QueueManager::class, function () {
            return new QueueManager();
        });
        
        // Register queue worker
        $app->singleton(Worker::class, function ($app) {
            return new Worker($app->make(QueueManager::class));
        });
        
        // Register queue as a shared alias
        $app->alias('queue', QueueManager::class);
        $app->alias('queue.worker', Worker::class);
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