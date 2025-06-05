<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The default database connection to use for queries. This can be overridden
    | for specific queries if needed.
    |
    */
    'default' => env('DB_CONNECTION', 'mysql'),
    
    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Configuration for all supported database systems. Each driver has its own
    | specific settings to optimize performance and feature availability.
    |
    */
    'connections' => [
        /*
        |--------------------------------------------------------------------------
        | SQLite Connection
        |--------------------------------------------------------------------------
        |
        | SQLite is a great choice for development, testing, or small applications.
        | It requires minimal setup and is file-based rather than server-based.
        |
        */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        
        /*
        |--------------------------------------------------------------------------
        | MySQL / MariaDB Connection
        |--------------------------------------------------------------------------
        |
        | MySQL is the most common database choice for PHP applications. MariaDB
        | is a fully compatible fork that offers enhanced features.
        |
        */
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'prefix_indexes' => true,
            'strict' => env('DB_STRICT_MODE', true),
            'engine' => env('DB_ENGINE', null),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]) : [],
        ],
        
        /*
        |--------------------------------------------------------------------------
        | PostgreSQL Connection
        |--------------------------------------------------------------------------
        |
        | PostgreSQL is a powerful, enterprise-class database with advanced features
        | like JSONB, robust full-text search, and excellent data integrity.
        |
        */
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
            'options' => [
                PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
            ],
        ],
        
        /*
        |--------------------------------------------------------------------------
        | SQL Server Connection
        |--------------------------------------------------------------------------
        |
        | Microsoft SQL Server configuration with proper encoding and schema support.
        | Requires the sqlsrv PHP extension or the pdo_sqlsrv driver.
        |
        */
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'sa'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'prefix_indexes' => true,
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', false),
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'options' => extension_loaded('sqlsrv') ? [
                PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
            ] : [],
        ],
        
        /*
        |--------------------------------------------------------------------------
        | Oracle Connection
        |--------------------------------------------------------------------------
        |
        | Oracle database configuration. Requires the oci8 or pdo_oci PHP extension.
        | This connection has specialized settings for Oracle environments.
        |
        */
        'oracle' => [
            'driver' => 'oracle',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1521'),
            'database' => env('DB_DATABASE', 'XE'),
            'service_name' => env('DB_SERVICE_NAME', ''),
            'username' => env('DB_USERNAME', 'system'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'AL32UTF8'),
            'prefix' => env('DB_PREFIX', ''),
            'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
            'edition' => env('DB_EDITION', 'ora$base'),
            'server_version' => env('DB_SERVER_VERSION', '11g'),
            'load_balance' => env('DB_LOAD_BALANCE', 'yes'),
            'dynamic' => [],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */
    'migrations' => 'migrations',
];
