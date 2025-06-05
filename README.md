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

## Getting Started

### Requirements

- PHP 8.0 or higher
- Composer
- MySQL, PostgreSQL, or SQLite

### Installation

```bash
composer create-project portfolion/portfolion my-project
cd my-project
php console serve
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
├── config/               # Configuration files
├── core/                 # Framework core code
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
    protected $table = 'tasks';
    
    protected $fillable = [
        'title', 'description', 'priority', 'completed'
    ];
    
    protected $casts = [
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

The Query Builder provides a fluent interface for working with databases.

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

## Advanced Features

Portfolion includes many advanced features for building robust applications. See the detailed documentation for more information:

- [Framework Documentation](FRAMEWORK_DOCUMENTATION.md) - Core features and advanced usage
- [Security Features](SECURITY_FEATURES.md) - Security best practices and tools

## Command Line Interface

Portfolion includes a command-line interface for common tasks:

```bash
# Start development server
php console serve

# Run database migrations
php console migrate

# Generate a new controller
php console make:controller UserController

# Clear application cache
php console cache:clear

# Run scheduled tasks
php console schedule:run

# Compile assets
php console assets:compile

# Generate API documentation
php console api:docs
```

## Testing

Run tests with PHPUnit:

```bash
# Run all tests
php console test

# Run specific test file
php console test --filter=UserTest
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The Portfolion framework is open-source software licensed under the [MIT license](LICENSE).