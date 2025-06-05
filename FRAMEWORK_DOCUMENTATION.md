# Portfolion Framework Documentation

## Table of Contents
- [Asset Management](#asset-management)
- [API Development](#api-development)
- [Logging System](#logging-system)
- [Rate Limiting](#rate-limiting)
- [Task Scheduling](#task-scheduling)

## Asset Management

The Portfolion framework includes a robust asset management system for handling CSS, JavaScript, and other web assets.

### Asset Manager

The `AssetManager` class provides tools for managing assets with features including:

- Version-based cache busting
- CDN integration
- Bundle management
- Minification support

#### Basic Usage

```php
// Register a CSS file
AssetManager::getInstance()->css('/css/app.css');

// Register a JavaScript file with defer attribute
AssetManager::getInstance()->js('/js/app.js', true);

// Add inline CSS/JS
AssetManager::getInstance()->inlineCss('.my-class { color: red; }');
AssetManager::getInstance()->inlineJs('console.log("Hello world");');

// Render assets in your template
echo AssetManager::getInstance()->renderCss();
echo AssetManager::getInstance()->renderJs();
```

#### Asset Bundles

Bundles allow you to group related assets:

```php
// Define bundles in config/assets.php
// Load a predefined bundle
AssetManager::getInstance()->bundle('app');
```

### Asset Compilation

The framework includes an asset compiler for SASS/SCSS and JavaScript with features:

- Source maps generation
- Minification
- File versioning
- Build manifest generation

#### Command Line Usage

```bash
# Compile assets once
php console assets:compile

# Watch for changes and recompile automatically
php console assets:compile --watch

# Compile with production settings (minification)
php console assets:compile --production
```

## API Development

Portfolion provides a complete API development system with standardized responses, resource transformations, and documentation.

### API Controllers

Extend the `ApiController` class to build API endpoints with standardized response methods:

```php
class UserController extends ApiController
{
    public function index()
    {
        $users = User::all();
        return $this->success(UserResource::collection($users));
    }
    
    public function store(UserRequest $request)
    {
        $user = User::create($request->validated());
        return $this->created(new UserResource($user));
    }
    
    public function notFound()
    {
        return $this->notFound('User not found');
    }
}
```

### API Resources

Transform your data models into consistent API responses:

```php
class UserResource extends ApiResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'created_at' => $this->resource->created_at,
        ];
    }
}
```

### Resource Collections

Automatically handle collections of resources, including pagination metadata:

```php
// In controller:
$users = User::paginate(15);
return $this->success(UserResource::collection($users));

// Output structure:
// {
//   "success": true,
//   "data": [...],
//   "meta": {
//     "current_page": 1,
//     "last_page": 5,
//     "per_page": 15,
//     "total": 68
//   },
//   "links": {...}
// }
```

### API Documentation

Generate OpenAPI documentation for your API endpoints:

```bash
# Generate API documentation
php console api:docs

# Generate and serve documentation with Swagger UI
php console api:docs --serve --port=8080
```

Documentation is automatically generated based on route definitions, controller methods, and request validation rules.

## Logging System

Portfolion includes a PSR-3 compatible logging system with support for multiple channels, formatters, and handlers.

### Basic Usage

```php
// Log to default channel
app()->get('log')->info('User logged in', ['user_id' => 1]);

// Log to specific channel
app()->get('log')->channel('security')->warning('Failed login attempt', [
    'ip' => $request->ip(),
    'username' => $request->input('username')
]);

// Structured logging
app()->get('log')->logStructured('info', 'Payment processed', [
    'amount' => $payment->amount,
    'status' => $payment->status,
    'customer_id' => $payment->customer_id
]);
```

### Available Channels

Configure multiple logging channels in `config/logging.php`:

- `single`: Logs to a single file
- `daily`: Rotates logs daily with automatic cleanup
- `syslog`: Logs to the system log
- `errorlog`: Logs to PHP's error_log
- `stack`: Combines multiple channels
- `json`: Logs in JSON format (good for structured data)

### Log Formatters

Customize the format of your logs:

- `LineFormatter`: Standard text-based format
- `JsonFormatter`: JSON format for machine-readable logs
- `HtmlFormatter`: HTML formatted logs (for web display)

## Rate Limiting

Protect your API from abuse with configurable rate limiting.

### Configuration

Configure rate limiters in `config/api.php`:

```php
'rate_limiting' => [
    'enabled' => env('API_RATE_LIMITING', true),
    
    'limiters' => [
        'default' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'by' => 'ip',
        ],
        'api_key' => [
            'max_attempts' => 300,
            'decay_minutes' => 1,
            'by' => 'api_key',
        ],
        // Add custom limiters as needed
    ],
],
```

### Usage in Routes

Apply rate limiting to specific routes:

```php
// In routes.php
$router->group(['middleware' => 'rate_limit:api_key'], function ($router) {
    $router->get('/api/data', 'DataController@index');
});
```

### Response Headers

Rate-limited responses include helpful headers:

- `X-RateLimit-Limit`: Maximum attempts allowed
- `X-RateLimit-Remaining`: Remaining attempts
- `X-RateLimit-Used`: Attempts used so far
- `X-RateLimit-Reset`: Time until limits reset (Unix timestamp)
- `Retry-After`: Seconds until next attempt is allowed (when limited)

## Task Scheduling

Schedule tasks to run automatically at specified intervals.

### Configuration

Define your scheduled tasks in `config/schedule.php`:

```php
use Portfolion\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    // Run a command every day at midnight
    $schedule->command('app:daily-report')
        ->daily()
        ->at('00:00')
        ->appendOutputTo(storage_path('logs/daily-report.log'));
        
    // Run a shell command every hour
    $schedule->exec('php -v')
        ->hourly()
        ->onOneServer()
        ->emailOutputOnFailure('admin@example.com');
        
    // Run a callback every 30 minutes
    $schedule->call(function () {
        // Your code here
    })->everyThirtyMinutes();
};
```

### Available Frequencies

- `->everyMinute()`
- `->everyFiveMinutes()`
- `->everyTenMinutes()`
- `->everyFifteenMinutes()`
- `->everyThirtyMinutes()`
- `->hourly()`
- `->daily()`
- `->weekly()`
- `->monthly()`
- `->quarterly()`
- `->yearly()`
- `->at('15:30')` - Run at a specific time
- `->cron('* * * * *')` - Use custom cron expression

### Setup

Add the scheduler to your system's crontab:

```
* * * * * cd /path-to-your-project && php console schedule:run >> /dev/null 2>&1
```

This will check for scheduled tasks every minute and run them when due. 