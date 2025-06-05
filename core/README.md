# Portfolion Framework Core Components

## Configuration Management

The `Config` class provides a robust configuration management system for the Portfolion framework. It handles loading configuration from multiple sources, caching for production environments, and validation of configuration values.

### Basic Usage

```php
use Portfolion\Config;

$config = Config::getInstance();

// Get configuration values
$dbHost = $config->get('database.host', 'localhost');
$debug = $config->get('app.debug', false);

// Set configuration values
$config->set('app.timezone', 'UTC');
$config->set('mail.from.address', 'no-reply@example.com');

// Validate configuration
$config->validate('database', [
    'host' => ['type' => 'string', 'required' => true],
    'port' => ['type' => 'integer', 'required' => true],
    'name' => ['type' => 'string', 'required' => true]
]);
```

### Configuration Files

Configuration files should be placed in the `config/` directory and return an array:

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', '')
        ]
    ]
];
```

### Environment Variables

Environment variables can be set in a `.env` file in the project root:

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=dbuser
DB_PASSWORD=secret
```

### Production Caching

In production environments, configuration is automatically cached to improve performance. To manually clear the cache:

```php
$config->clearCache();
```

### Configuration Validation

You can validate configuration values against a schema:

```php
$config->validate('mail', [
    'driver' => ['type' => 'string', 'required' => true],
    'host' => ['type' => 'string', 'required' => true],
    'port' => ['type' => 'integer', 'required' => true],
    'encryption' => ['type' => 'string', 'required' => false],
    'username' => ['type' => 'string', 'required' => true],
    'password' => ['type' => 'string', 'required' => true]
]);
```

### Best Practices

1. Always use environment variables for sensitive data
2. Cache configuration in production
3. Validate configuration early in the application lifecycle
4. Use dot notation for accessing nested configuration
5. Clear cache after updating configuration files
