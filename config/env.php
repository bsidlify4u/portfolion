<?php

/**
 * Environment Configuration
 * 
 * This file contains configuration for different environments.
 * The appropriate environment configuration will be loaded based on the APP_ENV value.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Environment Detection
    |--------------------------------------------------------------------------
    |
    | This value determines the environment the application is running in.
    | Available options: local, testing, staging, production
    |
    */
    'current' => env('APP_ENV', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Environment-specific Configurations
    |--------------------------------------------------------------------------
    |
    | Define environment-specific settings here. These will override the default
    | settings when the application is running in the specified environment.
    |
    */
    'environments' => [
        'local' => [
            'debug' => true,
            'display_errors' => true,
            'log_errors' => true,
            'error_reporting' => E_ALL,
            'cache' => false,
            'csrf_protection' => true,
            'rate_limiting' => false,
        ],
        
        'testing' => [
            'debug' => true,
            'display_errors' => true,
            'log_errors' => true,
            'error_reporting' => E_ALL,
            'cache' => false,
            'csrf_protection' => false,
            'rate_limiting' => false,
        ],
        
        'staging' => [
            'debug' => false,
            'display_errors' => false,
            'log_errors' => true,
            'error_reporting' => E_ALL & ~E_DEPRECATED,
            'cache' => true,
            'csrf_protection' => true,
            'rate_limiting' => true,
        ],
        
        'production' => [
            'debug' => false,
            'display_errors' => false,
            'log_errors' => true,
            'error_reporting' => E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
            'cache' => true,
            'csrf_protection' => true,
            'rate_limiting' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Environment Variable Loading
    |--------------------------------------------------------------------------
    |
    | Configure how environment variables are loaded.
    |
    */
    'env_files' => [
        '.env.php',
        '.env.local.php',
        '.env.' . env('APP_ENV', 'local') . '.php',
    ],
]; 