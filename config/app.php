<?php
return [
    'app' => [
        'debug' => false,
        'env' => 'production',
        'url' => 'http://localhost',
        'timezone' => 'UTC',
    ],
    'security' => [
        'csrf_lifetime' => 7200, // 2 hours
        'password_timeout' => 10800, // 3 hours
        'session_lifetime' => 120, // 2 hours
        'cipher' => 'AES-256-CBC',
        'allowed_hosts' => ['localhost'],
        'cors' => [
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'expose_headers' => [],
            'max_age' => 0,
            'supports_credentials' => false,
        ],
        'headers' => [
            'content-security-policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'x-frame-options' => 'SAMEORIGIN',
            'x-content-type-options' => 'nosniff',
            'x-xss-protection' => '1; mode=block',
            'referrer-policy' => 'strict-origin-when-cross-origin',
            'permissions-policy' => 'geolocation=(), camera=(), microphone=()',
        ],
        'csrf' => [
            'except' => [
                'api/*',
                'webhook/*',
            ],
        ],
    ],
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'portfolion',
                'username' => 'portfolion',
                'password' => 'portfolion',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
                'ssl' => [
                    'enabled' => true,
                    'verify' => true,
                ],
            ],
        ],
    ],
    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => storage_path('cache'),
            ],
        ],
        'prefix' => 'portfolion',
    ],
    'session' => [
        'driver' => 'file',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => true,
        'files' => storage_path('framework/sessions'),
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'portfolion_session',
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax',
        'start_automatically' => true,
        'id_lifetime' => 1800, // 30 minutes
        'prefix' => 'portfolion_session:',
    ],
    'redis' => [
        'client' => 'phpredis',
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
        'timeout' => 0.5,
        'read_timeout' => 60,
    ],
    'providers' => [
        // Core service providers
        \Portfolion\Providers\SessionServiceProvider::class,
        \Portfolion\Providers\CacheServiceProvider::class,
        \Portfolion\Providers\QueueServiceProvider::class,
    ],
];
