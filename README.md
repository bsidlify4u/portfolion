# Portfolion Framework

Portfolion is a modern, lightweight PHP framework for building web applications and APIs with a focus on simplicity, flexibility, and performance.

## Features

- **MVC Architecture**: Clean separation of concerns with Models, Views, and Controllers
- **Routing**: Flexible and intuitive routing system with support for RESTful resources
- **Database**: Powerful query builder and ORM with support for multiple database systems
- **Validation**: Comprehensive request validation system
- **Templating**: Simple, powerful templating with Twig integration
- **Assets**: Advanced asset management with bundling, minification, and versioning
- **API**: Complete API development tools with resources and OpenAPI documentation
- **Security**: Built-in protection against common web vulnerabilities
- **Logging**: PSR-3 compatible logging with multiple channels and formatters
- **CLI**: Command-line interface for common tasks and custom commands
- **Testing**: Integrated testing tools with PHPUnit support
- **Caching**: Robust caching system with multiple drivers (File, Redis, Memcached)
- **Queue**: Background job processing with multiple queue drivers
- **Rate Limiting**: Configurable rate limiting for API endpoints
- **Audit Logging**: Security-focused logging for sensitive operations

## Getting Started

### Requirements

- PHP 8.0 or higher
- Composer
- One of the supported database systems:
  - MySQL/MariaDB
  - PostgreSQL
  - SQLite
  - SQL Server
  - Oracle
  - IBM DB2

### Installation

```bash
composer create-project portfolion/portfolion my-project
cd my-project
php portfolion serve
```

Visit `http://localhost:8000` in your browser to see your new Portfolion application.

### Project Structure

```
my-project/
├── app/                  # Application code
│   ├── Controllers/      # Controller classes
│   ├── Models/           # Model classes
│   ├── Views/            # View templates
│   └── Middleware/       # HTTP middleware
├── bootstrap/            # Framework bootstrap files
├── config/               # Configuration files
├── core/                 # Framework core code
├── database/             # Database migrations and seeds
├── public/               # Publicly accessible files
├── resources/            # Application resources
│   ├── assets/           # Raw assets (SASS, JS)
│   ├── views/            # Twig view templates
│   └── lang/             # Localization files
├── routes/               # Route definitions
├── storage/              # Application storage
│   ├── app/              # Application storage
│   ├── logs/             # Log files
│   └── cache/            # Cache files
└── tests/                # Test files
```

## Configuration

Configuration files are stored in the `config/` directory. The framework loads these files based on the current environment.

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'Portfolion'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    // ...
];
```

## Routing

Define routes in the `routes/web.php` and `routes/api.php` files.

```php
// routes/web.php
$router->get('/', 'HomeController@index');
$router->get('/about', 'HomeController@about');
$router->post('/contact', 'ContactController@store');

// RESTful resource
$router->resource('tasks', 'TaskController');
```

## Controllers

Controllers handle HTTP requests and return responses.

```php
// app/Controllers/TaskController.php
namespace App\Controllers;

use Portfolion\Routing\Controller;
use Portfolion\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::all();
        return $this->view('tasks.index', ['tasks' => $tasks]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'nullable',
            'priority' => 'required|integer|min:1|max:5',
        ]);
        
        $task = Task::create($validated);
        
        return redirect('/tasks')->with('success', 'Task created successfully');
    }
}
```

## Models

Models represent database tables and provide an ORM interface.

```php
// app/Models/Task.php
namespace App\Models;

use Portfolion\Database\Model;

class Task extends Model
{
    protected ?string $table = 'tasks';
    
    protected array $fillable = [
        'title', 'description', 'priority', 'completed'
    ];
    
    protected array $casts = [
        'completed' => 'boolean',
        'priority' => 'integer',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## Views

Views render HTML using the Twig templating engine.

```twig
{# resources/views/tasks/index.twig #}
{% extends 'layouts/app.twig' %}

{% block content %}
    <h1>Task List</h1>
    
    {% if tasks is empty %}
        <p>No tasks found.</p>
    {% else %}
        <ul>
            {% for task in tasks %}
                <li>{{ task.title }} (Priority: {{ task.priority }})</li>
            {% endfor %}
        </ul>
    {% endif %}
    
    <a href="{{ url('/tasks/create') }}" class="btn">Create New Task</a>
{% endblock %}
```

## Database

The framework supports multiple database engines through PDO (PHP Data Objects):

- MySQL / MariaDB
- PostgreSQL
- SQLite
- SQL Server
- Oracle
- IBM DB2

### Query Builder

```php
// Using the Query Builder
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->where('active', true)
    ->orderBy('name')
    ->limit(10)
    ->get();

// Using the ORM
$tasks = Task::where('user_id', auth()->user()->id)
    ->where('completed', false)
    ->orderBy('priority', 'desc')
    ->paginate(15);
```

### Database Configuration

Configure your database connections in `config/database.php`:

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
            // ...
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            // ...
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            // ...
        ],
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            // ...
        ],
        'oci' => [
            'driver' => 'oci',
            // ...
        ],
        'ibm' => [
            'driver' => 'ibm',
            // ...
        ],
    ],
];
```

## Caching

The framework includes a robust caching system with multiple drivers:

```php
// Get cache instance
$cache = \Portfolion\Cache\Cache::getInstance();

// Basic operations
$cache->put('key', 'value', 60); // Store for 60 seconds
$value = $cache->get('key', 'default'); // Get with default fallback

// Remember pattern
$value = $cache->remember('key', 60, function() {
    return expensiveOperation();
});
```

## Queue System

Process background jobs with the queue system:

```php
// Create a job
class SendEmailJob extends Job
{
    protected $user;
    protected $message;
    
    public function __construct($user, $message)
    {
        $this->user = $user;
        $this->message = $message;
    }
    
    public function handle()
    {
        mail($this->user->email, 'Subject', $this->message);
    }
}

// Dispatch a job
Queue::push(new SendEmailJob($user, 'Hello!'));
```

## Security Features

The framework includes several built-in security features:

- **CSRF Protection**: Automatic protection against cross-site request forgery
- **Input Validation**: Comprehensive validation system
- **Secure Headers**: Automatically added security headers
- **Rate Limiting**: Protection against abuse and brute force attacks
- **Secure Logging**: Specialized channels for security-related events
- **Audit Logging**: Track sensitive operations and changes

## Testing

The framework includes a robust testing infrastructure:

```bash
# Run all tests
php portfolion test

# Run specific test suite
php portfolion test Unit

# Run tests with filter
php portfolion test --filter=UserTest

# Generate code coverage report
php portfolion test --coverage
```

## Command Line Interface

Portfolion includes a command-line interface for common tasks:

```bash
# Start development server
php portfolion serve

# Run database migrations
php portfolion migrate

# Generate a new controller
php portfolion make:controller UserController

# Clear application cache
php portfolion cache:clear

# Run scheduled tasks
php portfolion schedule:run

# Process jobs from the queue
php portfolion queue:work

# Run tests
php portfolion test
```

## Documentation

For more detailed information, see the following documentation:

- [Framework Documentation](FRAMEWORK_DOCUMENTATION.md) - Core features and advanced usage
- [Security Features](SECURITY_FEATURES.md) - Security best practices and tools
- [Database Support](README-DATABASE.md) - Database configuration and usage
- [Testing Infrastructure](TESTING-INFRASTRUCTURE.md) - Testing tools and best practices
- [Framework Features](README-FEATURES.md) - Detailed feature documentation

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The Portfolion Framework is open-source software licensed under the MIT license.