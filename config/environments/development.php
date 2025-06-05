<?php

/**
 * Development Environment Configuration
 * 
 * This file contains configuration settings specific to the development environment.
 * These settings override the default configuration when APP_ENV is set to 'development'.
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
        'enabled' => false, // Disable caching in development
        'ttl' => 60, // 1 minute for testing
        'prefix' => 'portfolion_dev_cache_',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    */
    'session' => [
        'secure' => false, // Allow HTTP in development
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
        'query_cache' => false, // Disable query caching for development
        'slow_query_log' => true,
        'slow_query_threshold' => 0.5, // seconds (lower threshold for development)
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'strict_transport_security' => false,
        'content_security_policy' => null, // Disable CSP in development
        'xss_protection' => true,
        'frame_options' => 'SAMEORIGIN', // Allow frames in development
        'content_type_options' => 'nosniff',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'opcache' => false, // Disable opcache in development
        'gzip_compression' => false,
        'minify_html' => false,
        'cache_control_max_age' => 0, // No caching for development
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'log' => true,
        'display' => true, // Show errors in development
        'report_to_sentry' => false, // Don't send errors to Sentry in development
        'sentry_dsn' => null,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Development Tools
    |--------------------------------------------------------------------------
    */
    'dev_tools' => [
        'enabled' => true,
        'debug_bar' => true,
        'query_logger' => true,
        'route_viewer' => true,
        'asset_watcher' => true,
    ],
]; 