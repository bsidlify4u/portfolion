# Database Support in Portfolion Framework

The Portfolion Framework supports multiple database engines through PDO (PHP Data Objects). This document outlines the supported database engines and how to configure them.

## Supported Database Engines

The framework supports the following database engines:

1. **MySQL / MariaDB** - Most commonly used with PHP frameworks
2. **PostgreSQL** - Fully supported with advanced SQL features
3. **SQLite** - Lightweight database, useful for testing and small projects
4. **SQL Server** - Microsoft's database solution
5. **Oracle** - Enterprise-grade database system
6. **IBM DB2** - Enterprise data server

## Configuration

Database configuration is stored in the `config/database.php` file. Here's an example configuration:

```php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                // Additional PDO options
            ],
        ],
        
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'options' => [
                // Additional PDO options
            ],
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'options' => [
                // Additional PDO options
            ],
        ],
        
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'sa'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'options' => [
                // Additional PDO options
            ],
        ],
        
        'oci' => [
            'driver' => 'oci',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1521'),
            'database' => env('DB_DATABASE', 'xe'),
            'username' => env('DB_USERNAME', 'system'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'options' => [
                // Additional PDO options
            ],
        ],
        
        'ibm' => [
            'driver' => 'ibm',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '50000'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'db2inst1'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'options' => [
                // Additional PDO options
            ],
        ],
    ],
];
```

## Environment Configuration

You can configure your database connection using environment variables in your `.env` file:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portfolion
DB_USERNAME=root
DB_PASSWORD=secret
```

## Migrations

The framework includes a migration system to manage database schema changes. Migrations are stored in the `database/migrations` directory.

### Running Migrations

To run migrations, use the following command:

```
php portfolion migrate
```

### Additional Migration Commands

- **Fresh Migration**: Drop all tables and re-run migrations
  ```
  php portfolion migrate --fresh
  ```

- **Rollback**: Revert the last batch of migrations
  ```
  php portfolion migrate --rollback
  ```

- **Reset**: Revert all migrations
  ```
  php portfolion migrate --reset
  ```

## Models

The framework uses an Active Record pattern for models. Models extend the `Portfolion\Database\Model` class and provide an intuitive interface for database operations.

Example model:

```php
<?php

namespace App\Models;

use Portfolion\Database\Model;

class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'users';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'name', 'email', 'password',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected array $hidden = [
        'password',
    ];
}
```

## Query Builder

The framework includes a query builder for constructing SQL queries:

```php
$users = $this->connection->table('users')
    ->where('active', '=', 1)
    ->orderBy('name', 'asc')
    ->limit(10)
    ->get();
``` 