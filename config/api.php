<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | This value determines the current version of your API.
    |
    */
    'version' => env('API_VERSION', '1.0.0'),
    
    /*
    |--------------------------------------------------------------------------
    | API Description
    |--------------------------------------------------------------------------
    |
    | A short description of your API for documentation.
    |
    */
    'description' => env('API_DESCRIPTION', 'API Documentation for the application'),
    
    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | This value is the URI prefix where your API will be accessible.
    |
    */
    'prefix' => env('API_PREFIX', 'api'),
    
    /*
    |--------------------------------------------------------------------------
    | API Domain
    |--------------------------------------------------------------------------
    |
    | This value is the domain where your API will be accessible.
    | Set to null to use the same domain as your web routes.
    |
    */
    'domain' => env('API_DOMAIN', null),
    
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Configure API authentication options.
    |
    */
    'auth' => [
        'bearer' => true,
        
        'oauth2' => [
            'enabled' => false,
            'flows' => [
                'password' => [
                    'tokenUrl' => '/oauth/token',
                    'refreshUrl' => '/oauth/token',
                    'scopes' => [],
                ],
            ],
        ],
        
        'apiKey' => [
            'enabled' => false,
            'in' => 'header', // header, query
            'name' => 'X-API-Key',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure API rate limiting.
    |
    */
    'rate_limiting' => [
        'enabled' => env('API_RATE_LIMITING', true),
        
        'limiters' => [
            'default' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
                'by' => 'ip',
            ],
            
            'auth' => [
                'max_attempts' => 30,
                'decay_minutes' => 1,
                'by' => 'user',
            ],
            
            'api_key' => [
                'max_attempts' => 300,
                'decay_minutes' => 1,
                'by' => 'api_key',
            ],
            
            'strict' => [
                'max_attempts' => 5,
                'decay_minutes' => 1,
                'by' => 'ip',
                'include_method' => true,
                'include_route' => true,
                'response_message' => 'Too many attempts. Please try again later.',
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Response Formatting
    |--------------------------------------------------------------------------
    |
    | Configure default API response format.
    |
    */
    'response' => [
        'always_wrap_data' => true, // Always wrap response in a 'data' key
        'include_status' => true,   // Include a 'success' status key
        'include_meta' => true,     // Include additional metadata where available
    ],
    
    /*
    |--------------------------------------------------------------------------
    | CORS Settings
    |--------------------------------------------------------------------------
    |
    | Cross-Origin Resource Sharing (CORS) settings for API requests.
    |
    */
    'cors' => [
        'enabled' => env('API_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('API_CORS_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-API-Key'],
        'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
        'max_age' => 86400, // 24 hours
        'allow_credentials' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Documentation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for API documentation generation.
    |
    */
    'documentation' => [
        'enabled' => env('API_DOCS_ENABLED', true),
        'route' => env('API_DOCS_ROUTE', '/api/docs'),
        'format' => env('API_DOCS_FORMAT', 'json'), // json or yaml
        'ui_enabled' => env('API_DOCS_UI_ENABLED', true), // Enable Swagger UI
        'auth_required' => env('API_DOCS_AUTH_REQUIRED', false), // Require auth to view docs
    ],
]; 