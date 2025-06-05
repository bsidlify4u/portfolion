<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'),
    
    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Each channel
    | can have a different driver and custom settings.
    |
    | Available Drivers: "single", "daily", "syslog", "errorlog", "null", "stack"
    |
    */
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'error'],
            'ignore_exceptions' => false,
        ],
        
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/app.log'),
            'level' => 'debug',
            'permission' => 0644,
            'formatter' => [
                'type' => 'line',
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'date_format' => 'Y-m-d H:i:s',
                'allow_inline_line_breaks' => false,
                'ignore_empty_context_and_extra' => true,
            ],
        ],
        
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/app.log'),
            'level' => 'debug',
            'days' => 14,
            'permission' => 0644,
            'formatter' => [
                'type' => 'line',
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'date_format' => 'Y-m-d H:i:s',
                'allow_inline_line_breaks' => false,
                'ignore_empty_context_and_extra' => true,
            ],
        ],
        
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 30,
            'permission' => 0644,
        ],
        
        'json' => [
            'driver' => 'single',
            'path' => storage_path('logs/app.json.log'),
            'level' => 'debug',
            'formatter' => [
                'type' => 'json',
                'batch_mode' => 2, // BATCH_MODE_NEWLINES
                'append_new_line' => true,
                'ignore_empty_context_and_extra' => true,
            ],
        ],
        
        'syslog' => [
            'driver' => 'syslog',
            'ident' => env('APP_NAME', 'portfolion'),
            'facility' => LOG_USER,
            'level' => 'debug',
        ],
        
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],
        
        'null' => [
            'driver' => 'null',
        ],
        
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'info',
            'days' => 90,
            'permission' => 0600,
        ],
        
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,
            'formatter' => [
                'type' => 'json',
                'batch_mode' => 2, // BATCH_MODE_NEWLINES
                'append_new_line' => true,
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Log Processors
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log processors that should be applied to all
    | log records before they are handled by the handlers. These are useful
    | for adding common data to all log records.
    |
    */
    'processors' => [
        // Web request processor
        function (array $record) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $record['extra']['request_uri'] = $_SERVER['REQUEST_URI'];
                $record['extra']['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
                $record['extra']['client_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            }
            
            return $record;
        },
        
        // Add current user ID if available
        function (array $record) {
            if (function_exists('auth') && ($user = auth()->user())) {
                $record['extra']['user_id'] = $user->getId();
            }
            
            return $record;
        },
    ],
]; 