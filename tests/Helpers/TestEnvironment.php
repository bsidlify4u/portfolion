<?php

namespace Tests\Helpers;

/**
 * Helper class for setting up test environments
 */
class TestEnvironment
{
    /**
     * Set up in-memory SQLite for testing
     * 
     * @return void
     */
    public static function setupDatabaseForTesting(): void
    {
        // Force SQLite in-memory for consistent testing
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('USE_TEST_CONFIG=true');
        putenv('APP_ENV=testing');
    }
    
    /**
     * Set up default test configuration values
     * 
     * @return void
     */
    public static function setupTestConfig(): void
    {
        // Set up basic testing environment variables
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');
        putenv('APP_KEY=base64:testingtestingtestingtestingtesting==');
        putenv('APP_URL=http://localhost');
        putenv('CACHE_DRIVER=array');
        putenv('SESSION_DRIVER=array');
        putenv('USE_TEST_CONFIG=true');
        putenv('LOG_CHANNEL=stderr');
        putenv('LOG_LEVEL=debug');
        
        // Ensure these are also available in the $_ENV array
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_KEY'] = 'base64:testingtestingtestingtestingtesting==';
        $_ENV['APP_URL'] = 'http://localhost';
        $_ENV['CACHE_DRIVER'] = 'array';
        $_ENV['SESSION_DRIVER'] = 'array';
        $_ENV['USE_TEST_CONFIG'] = 'true';
        $_ENV['LOG_CHANNEL'] = 'stderr';
        $_ENV['LOG_LEVEL'] = 'debug';
    }
    
    /**
     * Create standard test tables for database tests
     * 
     * @param \Portfolion\Database\Connection $connection
     * @return bool Success
     */
    public static function createTestTables($connection): bool
    {
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
            
            // Create tasks table for controller tests
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
            
            // Create configs table for config tests
            $connection->execute('
                CREATE TABLE IF NOT EXISTS configs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    section VARCHAR(255) NOT NULL,
                    key VARCHAR(255) NOT NULL,
                    value TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    UNIQUE(section, key)
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
        } catch (\PDOException $e) {
            echo 'Error setting up test database: ' . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
    
    /**
     * Create a mock Redis instance for tests
     * 
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createRedisMock($testCase)
    {
        $redisMock = $testCase->createMock(\Redis::class);
        
        // Set up storage for our mock
        $data = [];
        
        // Configure the mock to return values
        $redisMock->method('hGet')
            ->willReturnCallback(function($key, $field) use (&$data) {
                return $data[$key][$field] ?? null;
            });
            
        $redisMock->method('hSet')
            ->willReturnCallback(function($key, $field, $value) use (&$data) {
                $data[$key][$field] = $value;
                return true;
            });
            
        $redisMock->method('hGetAll')
            ->willReturnCallback(function($key) use (&$data) {
                return $data[$key] ?? [];
            });
            
        $redisMock->method('exists')
            ->willReturn(true);
            
        $redisMock->method('del')
            ->willReturnCallback(function($key) use (&$data) {
                unset($data[$key]);
                return true;
            });
            
        return $redisMock;
    }
} 