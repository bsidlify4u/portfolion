<?php

/**
 * Portfolion Framework Setup Script
 * 
 * This script initializes the framework for development by:
 * 1. Creating necessary directories
 * 2. Setting up configuration files
 * 3. Creating a sample .env file
 * 4. Setting up the database
 * 5. Running initial migrations
 */

echo "╔════════════════════════════════════════╗\n";
echo "║     Portfolion Framework Setup         ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// Function to create directory if it doesn't exist
function createDirectory($path) {
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✓ Created directory: $path\n";
        } else {
            echo "✗ Failed to create directory: $path\n";
        }
    } else {
        echo "✓ Directory already exists: $path\n";
    }
}

// Function to create file with content if it doesn't exist
function createFile($path, $content) {
    if (!file_exists($path)) {
        if (file_put_contents($path, $content)) {
            echo "✓ Created file: $path\n";
        } else {
            echo "✗ Failed to create file: $path\n";
        }
    } else {
        echo "✓ File already exists: $path\n";
    }
}

// 1. Create necessary directories
echo "\n[1/5] Creating necessary directories...\n";
$directories = [
    'app/Controllers',
    'app/Models',
    'app/Middleware',
    'app/Services',
    'config',
    'database/migrations',
    'database/seeds',
    'public',
    'resources/views',
    'resources/assets/css',
    'resources/assets/js',
    'routes',
    'storage/app',
    'storage/cache',
    'storage/logs',
    'storage/sessions',
    'storage/uploads',
    'tests',
];

foreach ($directories as $dir) {
    createDirectory($dir);
}

// 2. Set up configuration files
echo "\n[2/5] Setting up configuration files...\n";

// Database configuration
$dbConfig = <<<'EOT'
<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', 'database/database.sqlite'),
            'prefix' => '',
        ],
    ],
];
EOT;

createFile('config/database.php', $dbConfig);

// App configuration
$appConfig = <<<'EOT'
<?php

return [
    'name' => env('APP_NAME', 'Portfolion'),
    'env' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'key' => env('APP_KEY', 'base64:'.base64_encode(random_bytes(32))),
];
EOT;

createFile('config/app.php', $appConfig);

// Cache configuration
$cacheConfig = <<<'EOT'
<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),
    
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => 'storage/cache',
        ],
        'array' => [
            'driver' => 'array',
        ],
    ],
];
EOT;

createFile('config/cache.php', $cacheConfig);

// Routes file
$routesFile = <<<'EOT'
<?php

use Portfolion\Routing\Router;

// Define your routes here
Router::get('/', 'HomeController@index');

// API routes
Router::group(['prefix' => 'api'], function() {
    Router::get('users', 'Api\UserController@index');
});
EOT;

createFile('routes/web.php', $routesFile);

// 3. Create a sample .env file
echo "\n[3/5] Creating sample .env file...\n";

$envContent = <<<'EOT'
APP_NAME=Portfolion
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=portfolion
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
EOT;

createFile('.env.example', $envContent);
createFile('.env', $envContent);

// 4. Set up the database
echo "\n[4/5] Setting up the database...\n";
echo "To set up your database, run the following commands:\n";
echo "- For MySQL: CREATE DATABASE portfolion;\n";
echo "- For SQLite: touch database/database.sqlite\n";
echo "Then update your .env file with the appropriate database credentials.\n";

// 5. Create sample migration files
echo "\n[5/5] Creating sample migration files...\n";

$usersMigration = <<<'EOT'
<?php

namespace Database\Migrations;

use Portfolion\Database\Migration;
use Portfolion\Database\Schema\Blueprint;

class CreateUsersTable extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->dropIfExists('users');
    }
}
EOT;

createFile('database/migrations/2023_01_01_000001_create_users_table.php', $usersMigration);

echo "\n✓ Setup completed successfully!\n";
echo "\nTo start developing with Portfolion:\n";
echo "1. Configure your database in .env\n";
echo "2. Run migrations: php portfolion migrate\n";
echo "3. Start the development server: php portfolion serve\n";
echo "\nHappy coding!\n"; 