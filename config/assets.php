<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asset Path
    |--------------------------------------------------------------------------
    |
    | This value determines the public path for assets. By default, this will
    | use the public directory in the root of your application.
    |
    */
    'public_path' => public_path(),
    
    /*
    |--------------------------------------------------------------------------
    | CDN URL
    |--------------------------------------------------------------------------
    |
    | This URL is used when serving assets from a CDN. Leave this null to
    | use the local path. When set, all assets will be served from this URL.
    |
    */
    'cdn_url' => env('ASSET_CDN_URL', null),
    
    /*
    |--------------------------------------------------------------------------
    | Minification
    |--------------------------------------------------------------------------
    |
    | This determines whether assets should be minified in production.
    | This only applies to assets handled by the built-in compiler.
    |
    */
    'minify' => [
        'enabled' => env('APP_ENV') === 'production',
        'css' => true,
        'js' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Busting
    |--------------------------------------------------------------------------
    |
    | Configure how the framework handles cache busting for assets.
    |
    */
    'cache_busting' => [
        'strategy' => env('ASSET_CACHE_STRATEGY', 'query'), // 'query', 'filename', or 'manifest'
        'length' => 8, // Length of the hash for filename strategy
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Asset Bundles
    |--------------------------------------------------------------------------
    |
    | Define groups of assets that can be loaded together as a bundle.
    |
    */
    'bundles' => [
        'app' => [
            'css' => [
                ['path' => '/css/app.css', 'priority' => 5],
            ],
            'js' => [
                ['path' => '/js/app.js', 'defer' => true, 'priority' => 10],
            ],
        ],
        
        'dashboard' => [
            'css' => [
                ['path' => '/css/dashboard.css', 'priority' => 5],
            ],
            'js' => [
                ['path' => '/js/dashboard.js', 'defer' => true, 'priority' => 10],
            ],
        ],
        
        'vendors' => [
            'css' => [
                ['path' => '/css/vendors.css', 'priority' => 1],
            ],
            'js' => [
                ['path' => '/js/vendors.js', 'defer' => true, 'priority' => 1],
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Compilation
    |--------------------------------------------------------------------------
    |
    | Configure the built-in asset compilation system.
    |
    */
    'compilation' => [
        'enabled' => true,
        'source_dir' => resource_path('assets'),
        'output_dir' => public_path(),
        'manifest_path' => public_path('mix-manifest.json'),
        
        // SASS/SCSS compilation settings
        'sass' => [
            'enabled' => true,
            'source_dir' => resource_path('assets/sass'),
            'output_dir' => public_path('css'),
            'options' => [
                'autoprefixer' => true,
                'source_maps' => env('APP_DEBUG', false),
            ],
        ],
        
        // JavaScript compilation settings
        'js' => [
            'enabled' => true,
            'source_dir' => resource_path('assets/js'),
            'output_dir' => public_path('js'),
            'babel' => true,
            'options' => [
                'source_maps' => env('APP_DEBUG', false),
            ],
        ],
    ],
]; 