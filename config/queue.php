<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The framework's queue API supports a variety of drivers including
    | "database", "sync", "redis", and "null". The "sync" driver will
    | synchronously execute jobs (suitable for local development).
    |
    */
    'driver' => env('QUEUE_DRIVER', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. Default configurations have been added
    | for each driver. You are free to add more connections as needed.
    |
    */
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */
    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the queue worker's behavior including the number
    | of tries and the timeout in seconds. These values are used when you
    | run the work command.
    |
    */
    'worker' => [
        'tries' => env('QUEUE_TRIES', 3),
        'timeout' => env('QUEUE_TIMEOUT', 60),
        'sleep' => env('QUEUE_SLEEP', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Prefix
    |--------------------------------------------------------------------------
    |
    | If you are running multiple sites on a single server, you may want to
    | specify a prefix that is used for your Portfolion queue. This will
    | prevent job name collisions between different applications.
    |
    */
    'prefix' => env('QUEUE_PREFIX', 'portfolion_queue:'),
]; 