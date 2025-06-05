<?php

namespace App\Providers;

use Portfolion\Container\Container;
use Portfolion\View\TwigTemplate;

class ViewServiceProvider
{
    /**
     * Register any view services.
     *
     * @return void
     */
    public function register(): void
    {
        $container = Container::getInstance();
        
        // Register TwigTemplate as a singleton
        $container->singleton(TwigTemplate::class);
    }

    /**
     * Bootstrap any view services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Create the storage/cache/twig directory if it doesn't exist
        $cachePath = dirname(dirname(__DIR__)) . '/storage/cache/twig';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }
} 