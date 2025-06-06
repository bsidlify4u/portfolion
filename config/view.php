<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default View Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default view engine that will be used to render
    | views. Supported: "php", "twig", "blade"
    |
    */
    'default' => env('VIEW_ENGINE', 'php'),
    
    /*
    |--------------------------------------------------------------------------
    | View Paths
    |--------------------------------------------------------------------------
    |
    | These are the paths where the framework will look for views.
    |
    */
    'paths' => [
        'php' => 'resources/views',
        'twig' => 'resources/views',
        'blade' => 'resources/views',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Twig Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Twig environment options.
    |
    */
    'twig' => [
        'enabled' => true,
        'cache' => env('VIEW_CACHE', false) ? 'storage/cache/twig' : false,
        'debug' => env('APP_DEBUG', false),
        'auto_reload' => true,
        'strict_variables' => false,
        'file_extension' => '.twig',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Blade Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Blade compiler options.
    |
    */
    'blade' => [
        'enabled' => true,
        'cache' => 'storage/cache/blade',
        'file_extension' => '.blade.php',
    ],
]; 