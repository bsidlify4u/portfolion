# Database Support in Portfolion

Portfolion supports multiple database engines to provide flexibility for different project requirements. The database layer is built on top of PHP's PDO, ensuring a consistent API while leveraging database-specific features.

## Supported Database Engines

### MySQL / MariaDB

MySQL is the most widely used database with PHP applications, and Portfolion provides robust support for both MySQL and its fork, MariaDB.

**Features:**
- Full support for all MySQL/MariaDB specific SQL syntax
- InnoDB engine by default for transaction support
- Native JSON data type support (MySQL 5.7+)
- Proper UTF-8 encoding with `utf8mb4` charset

### PostgreSQL

PostgreSQL is a powerful, enterprise-class database system with an emphasis on extensibility and standards compliance.

**Features:**
- Native JSONB support for efficient JSON storage and indexing
- Advanced indexing options (GIN, GiST)
- Full-text search capabilities
- Schema support with search path configuration

### SQLite

SQLite is a self-contained, serverless database engine that's ideal for development, testing, or small applications.

**Features:**
- File-based or in-memory database options
- Zero configuration required
- Foreign key constraint support (optional)
- Great for testing and development environments

### Microsoft SQL Server

SQL Server support is provided for environments where Microsoft's database solution is required.

**Features:**
- Support for SQL Server-specific syntax
- Proper handling of identifiers with square brackets
- Unicode text storage with NVARCHAR
- Configuration options for secure connections

### Oracle

Oracle database support is available for enterprise environments that require it.

**Features:**
- Connection support via SID, Service Name, or TNS
- Proper sequence handling for auto-incrementing fields
- CLOB data type for JSON and large text storage
- Date and timestamp format configuration

## Configuration

Database configuration is managed in the `config/database.php` file, where you can define multiple connection configurations and set the default connection.

```php
// Example database configuration
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'portfolion'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            // additional options...
        ],
        
        // Additional connection configurations...
    ],
];
```

## Usage

### Basic Query Builder

```php
use Portfolion\Database\DB;

// Select query
$users = DB::table('users')
    ->where('status', '=', 'active')
    ->orderBy('name', 'asc')
    ->get();

// Insert data
$id = DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Update data
DB::table('users')
    ->where('id', '=', 1)
    ->update(['status' => 'inactive']);

// Delete data
DB::table('users')
    ->where('status', '=', 'deleted')
    ->delete();
```

### Schema Builder

```php
use Portfolion\Database\Schema\Schema;

// Create a table
Schema::create('users', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

// Modify a table
Schema::table('users', function ($table) {
    $table->string('phone')->nullable();
});

// Drop a table
Schema::drop('users');
```

## Database-Specific Features

Portfolion automatically adapts to the database engine you're using, but you can also check for specific feature support:

```php
use Portfolion\Database\DB;

$connection = DB::connection();

if ($connection->supportsFeature('json')) {
    // Use JSON features
}

if ($connection->supportsFeature('fulltext')) {
    // Use full-text search capabilities
}
```

## Testing with Different Databases

For testing, you can configure which database to use via environment variables:

```bash
# Test with SQLite (default)
TEST_DB_CONNECTION=sqlite TEST_DB_IN_MEMORY=true phpunit

# Test with MySQL
TEST_DB_CONNECTION=mysql TEST_DB_DATABASE=portfolion_test phpunit

# Test with PostgreSQL
TEST_DB_CONNECTION=pgsql TEST_DB_DATABASE=portfolion_test phpunit
```

When testing with real database engines (not SQLite), make sure to create the test database beforehand.

## Performance Optimization

- Connection pooling is supported for MySQL and PostgreSQL
- Prepared statements are used to prevent SQL injection and improve performance
- Database queries are logged in development mode for performance analysis
- Index usage is encouraged through the schema builder's index methods 