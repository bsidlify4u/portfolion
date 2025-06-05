<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load test environment configuration
$testConfig = require_once __DIR__ . '/../test_config/env.testing.php';

// Apply test environment settings
foreach ($testConfig as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Set up testing environment
putenv('APP_ENV=testing');

// Default to SQLite for testing unless specified otherwise
$testDbConnection = getenv('TEST_DB_CONNECTION') ?: 'sqlite';
putenv('DB_CONNECTION=' . $testDbConnection);

// Configure database connections for testing based on driver
switch ($testDbConnection) {
    case 'mysql':
    case 'mariadb':
        // Use an isolated test database for MySQL/MariaDB
        putenv('DB_DATABASE=portfolion_test');
        putenv('DB_USERNAME=' . (getenv('TEST_DB_USERNAME') ?: 'root'));
        putenv('DB_PASSWORD=' . (getenv('TEST_DB_PASSWORD') ?: ''));
        putenv('DB_HOST=' . (getenv('TEST_DB_HOST') ?: 'localhost'));
        putenv('DB_PORT=' . (getenv('TEST_DB_PORT') ?: '3306'));
        break;
        
    case 'pgsql':
        // Use an isolated test database for PostgreSQL
        putenv('DB_DATABASE=portfolion_test');
        putenv('DB_USERNAME=' . (getenv('TEST_DB_USERNAME') ?: 'postgres'));
        putenv('DB_PASSWORD=' . (getenv('TEST_DB_PASSWORD') ?: ''));
        putenv('DB_HOST=' . (getenv('TEST_DB_HOST') ?: 'localhost'));
        putenv('DB_PORT=' . (getenv('TEST_DB_PORT') ?: '5432'));
        break;
        
    case 'sqlsrv':
        // Use an isolated test database for SQL Server
        putenv('DB_DATABASE=portfolion_test');
        putenv('DB_USERNAME=' . (getenv('TEST_DB_USERNAME') ?: 'sa'));
        putenv('DB_PASSWORD=' . (getenv('TEST_DB_PASSWORD') ?: ''));
        putenv('DB_HOST=' . (getenv('TEST_DB_HOST') ?: 'localhost'));
        putenv('DB_PORT=' . (getenv('TEST_DB_PORT') ?: '1433'));
        break;
        
    case 'oracle':
    case 'oci':
        // Use an isolated test database for Oracle
        putenv('DB_DATABASE=XE');
        putenv('DB_SERVICE_NAME=' . (getenv('TEST_DB_SERVICE_NAME') ?: ''));
        putenv('DB_USERNAME=' . (getenv('TEST_DB_USERNAME') ?: 'system'));
        putenv('DB_PASSWORD=' . (getenv('TEST_DB_PASSWORD') ?: ''));
        putenv('DB_HOST=' . (getenv('TEST_DB_HOST') ?: 'localhost'));
        putenv('DB_PORT=' . (getenv('TEST_DB_PORT') ?: '1521'));
        break;
        
    case 'sqlite':
    default:
        // Use SQLite in-memory for fastest tests or file-based for persistence
        $useInMemory = getenv('TEST_DB_IN_MEMORY') !== 'false';
        
        if ($useInMemory) {
            putenv('DB_DATABASE=:memory:');
        } else {
            $dbFile = __DIR__ . '/../storage/database/testing.sqlite';
            putenv('DB_DATABASE=' . $dbFile);
            
            // Create database directory if it doesn't exist
            $dbDir = dirname($dbFile);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // Create empty database file if it doesn't exist
            if (!file_exists($dbFile)) {
                touch($dbFile);
            }
        }
        break;
}

// Initialize the application for testing
$app = new Portfolion\Application();
$app->bootstrap();

// Clear the config cache to ensure test settings are used
$config = Portfolion\Config::getInstance();
$config->clearCache();
$config->enableTestMode();

// Set up database connection
$db = Portfolion\Database\DB::connection();

// Migrate the database schema for testing
if ($testDbConnection === 'sqlite' && getenv('DB_DATABASE') === ':memory:') {
    // For in-memory SQLite, run migrations immediately
    $schema = createTestSchema($db);
}

/**
 * Create a minimal test schema for database tests
 * 
 * @param Portfolion\Database\Connection $connection
 * @return bool
 */
function createTestSchema($connection) {
    try {
        // Create users table
        $connection->execute('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                remember_token VARCHAR(100),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ');
        
        // Create tasks table for TaskController tests
        $connection->execute('
            CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                status VARCHAR(50) DEFAULT "pending",
                priority INTEGER DEFAULT 0,
                due_date DATE,
                user_id INTEGER,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ');
        
        // Create migrations table
        $connection->execute('
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )
        ');
        
        // Add some test data
        $connection->execute("
            INSERT INTO users (name, email, password, created_at, updated_at)
            VALUES ('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', datetime('now'), datetime('now'))
        ");
        
        $connection->execute("
            INSERT INTO tasks (title, description, status, priority, user_id, created_at, updated_at)
            VALUES ('Test Task', 'This is a test task', 'pending', 1, 1, datetime('now'), datetime('now'))
        ");
        
        return true;
    } catch (PDOException $e) {
        echo 'Error setting up test database: ' . $e->getMessage() . PHP_EOL;
        return false;
    }
}

// Additional test setup can be added here