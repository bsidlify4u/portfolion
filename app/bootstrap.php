<?php

/**
 * Bootstrap application services
 */

// Register service providers
$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
];

// Initialize service providers
foreach ($providers as $provider) {
    $instance = new $provider();
    if (method_exists($instance, 'register')) {
        $instance->register();
    }
    if (method_exists($instance, 'boot')) {
        $instance->boot();
    }
} 