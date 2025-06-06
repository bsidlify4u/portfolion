# Portfolion PHP Framework

A lightweight, modern PHP framework for building web applications and APIs.

## Features

- **MVC Architecture** - Organized structure with Models, Views, and Controllers
- **Routing System** - Simple and flexible routing
- **Database ORM** - Intuitive database interaction
- **Migration System** - Easy database schema management
- **Command Line Interface** - Powerful CLI tools for development
- **Caching System** - Efficient caching mechanisms
- **Task Scheduling** - Automated task scheduling
- **Middleware Support** - Request/response filtering
- **Multiple Template Engines** - Support for PHP, Twig, and Blade templating

## Requirements

- PHP 8.0 or higher
- Composer
- MySQL, SQLite, or PostgreSQL

## Installation

### Option 1: Clone the Repository

```bash
git clone https://github.com/yourusername/portfolion.git
cd portfolion
composer install
php setup.php
```

### Option 2: Create a New Project (via Composer)

```bash
composer create-project portfolion/portfolion your-project-name
cd your-project-name
php setup.php
```

## Quick Start

1. Configure your database in `.env`
2. Run migrations:
   ```bash
   php portfolion migrate
   ```
3. Start the development server:
   ```bash
   php portfolion serve
   ```
4. Visit `http://localhost:8000` in your browser

## Directory Structure

```
portfolion/
├── app/                  # Application code
│   ├── Controllers/      # Controller classes
│   ├── Models/           # Model classes
│   ├── Middleware/       # Middleware classes
│   └── Services/         # Service classes
├── config/               # Configuration files
├── core/                 # Framework core files
├── database/             # Database files
│   ├── migrations/       # Database migrations
│   └── seeds/            # Database seeders
├── public/               # Publicly accessible files
├── resources/            # Resources
│   ├── assets/           # Assets (CSS, JS, etc.)
│   └── views/            # View templates
│       ├── layouts/      # Layout templates
│       └── partials/     # Partial templates
├── routes/               # Route definitions
├── storage/              # Storage files
│   ├── app/              # Application storage
│   ├── cache/            # Cache files
│   │   ├── blade/        # Blade cache files
│   │   └── twig/         # Twig cache files
│   ├── logs/             # Log files
│   └── sessions/         # Session files
└── tests/                # Test files
```

## Command Line Interface

Portfolion comes with a powerful CLI tool to help with common development tasks:

```bash
# List all available commands
php portfolion

# Create a new controller
php portfolion make:controller UserController

# Create a new model
php portfolion make:model User

# Run database migrations
php portfolion migrate

# Rollback migrations
php portfolion migrate --rollback

# Reset all migrations
php portfolion migrate --reset

# Clear application cache
php portfolion cache:clear

# Run scheduled tasks
php portfolion schedule:run
```

## Database Migrations

Create a new migration:

```bash
php portfolion make:migration create_posts_table
```

Migration file structure:

```php
<?php

namespace Database\Migrations;

use Portfolion\Database\Migration;
use Portfolion\Database\Schema\Blueprint;

class CreatePostsTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('posts');
    }
}
```

## Models

```php
<?php

namespace App\Models;

use Portfolion\Database\Model;

class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'name', 'email', 'password'
    ];
    
    // Define relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

## Controllers

```php
<?php

namespace App\Controllers;

use Portfolion\Http\Controller;
use Portfolion\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all();
        return $this->view('users.index', ['users' => $users]);
    }
    
    public function show(Request $request, $id)
    {
        $user = User::find($id);
        return $this->view('users.show', ['user' => $user]);
    }
}
```

## Routes

```php
<?php

use Portfolion\Routing\Router;

Router::get('/', 'HomeController@index');
Router::get('/users', 'UserController@index');
Router::get('/users/{id}', 'UserController@show');

// Route groups
Router::group(['prefix' => 'admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'Admin\DashboardController@index');
});

// API routes
Router::group(['prefix' => 'api'], function() {
    Router::get('/users', 'Api\UserController@index');
});
```

## Template Engines

Portfolion supports multiple template engines:

### PHP Templates

```php
<!-- resources/views/welcome.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <h1><?= $title ?></h1>
    <p><?= $content ?></p>
</body>
</html>
```

### Twig Templates

```twig
{# resources/views/welcome.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ title }}</h1>
    <p>{{ content }}</p>
    
    {% for item in items %}
        <li>{{ item }}</li>
    {% endfor %}
</body>
</html>
```

### Blade Templates

```blade
<!-- resources/views/welcome.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $content }}</p>
    
    @foreach($items as $item)
        <li>{{ $item }}</li>
    @endforeach
</body>
</html>
```

### Using Different Template Engines

```php
// Using the default template engine (configured in config/view.php)
return $this->view('welcome', ['title' => 'Welcome']);

// Using Twig
return $this->view('welcome', ['title' => 'Welcome'], 'twig');

// Using Blade
return $this->view('welcome', ['title' => 'Welcome'], 'blade');
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.