<?php

namespace App\Providers;

use App\Http\Controllers\TaskController;
use Portfolion\Container\Container;

class AppServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $container = Container::getInstance();
        
        // Register controllers as singletons
        $container->singleton(TaskController::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
} 