<?php

/**
 * Production Environment Configuration
 * 
 * This file contains configuration settings specific to the production environment.
 * These settings override the default configuration when APP_ENV is set to 'production'.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */
    'app' => [
        'debug' => false,
        'log_level' => 'error',
        'display_errors' => false,
        'timezone' => 'UTC',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'portfolion_cache_',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    */
    'session' => [
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax',
        'lifetime' => 120, // 2 hours
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'query_cache' => true,
        'slow_query_log' => true,
        'slow_query_threshold' => 1.0, // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'strict_transport_security' => true,
        'content_security_policy' => "default-src 'self'; script-src 'self'; style-src 'self';",
        'xss_protection' => true,
        'frame_options' => 'DENY',
        'content_type_options' => 'nosniff',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'opcache' => true,
        'gzip_compression' => true,
        'minify_html' => true,
        'cache_control_max_age' => 31536000, // 1 year for static assets
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'log' => true,
        'display' => false,
        'report_to_sentry' => true,
        'sentry_dsn' => env('SENTRY_DSN', ''),
    ],
]; 