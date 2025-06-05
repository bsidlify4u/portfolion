<?php

/**
 * Testing Environment Configuration
 * 
 * This file contains configuration settings specific to the testing environment.
 * These settings override the default configuration when APP_ENV is set to 'testing'.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */
    'app' => [
        'debug' => true,
        'log_level' => 'debug',
        'display_errors' => true,
        'timezone' => 'UTC',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => false, // Disable caching in tests
        'ttl' => 60,
        'prefix' => 'portfolion_test_cache_',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    */
    'session' => [
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
        'lifetime' => 120,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'default' => env('DB_CONNECTION', 'sqlite'),
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => base_path('storage/database/testing.sqlite'), // Use a real file for persistence
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'portfolion_test'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => 'InnoDB',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'portfolion_test'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ],
        ],
        'query_cache' => false,
        'slow_query_log' => false,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'strict_transport_security' => false,
        'content_security_policy' => null,
        'xss_protection' => true,
        'frame_options' => 'SAMEORIGIN',
        'content_type_options' => 'nosniff',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'log' => true,
        'display' => true,
        'report_to_sentry' => false,
        'sentry_dsn' => null,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Testing Settings
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'mock_services' => false, // Don't mock services, use real ones
        'disable_middleware' => false, // Run with middleware
        'disable_events' => false, // Run with events
        'fake_filesystem' => false, // Use real filesystem
    ],
]; 