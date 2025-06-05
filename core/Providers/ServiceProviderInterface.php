<?php

namespace Portfolion\Providers;

use Portfolion\App;

/**
 * Interface for service providers
 */
interface ServiceProviderInterface
{
    /**
     * Register the service provider
     * 
     * @param App $app Application instance
     * @return void
     */
    public function register(App $app): void;
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot(): void;
} 