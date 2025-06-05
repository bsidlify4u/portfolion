<?php

namespace Tests\Database;

use Tests\TestCase;
use Portfolion\Database\Connection;
use PDO;
use PDOException;

abstract class DatabaseTestCase extends TestCase
{
    /**
     * @var Connection
     */
    protected $connection;
    
    /**
     * @var string
     */
    protected $driver;
    
    /**
     * @var array Tables created during the test
     */
    protected $createdTables = [];
    
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            // Force in-memory SQLite for tests unless explicitly configured otherwise
            if (!getenv('TEST_DB_CONNECTION')) {
                putenv('DB_CONNECTION=sqlite');
                putenv('DB_DATABASE=:memory:');
            }
            
            $this->connection = new Connection();
            $this->driver = $this->connection->getDriver();
            
            // Create the migrations table if it doesn't exist
            $this->createMigrationsTable();
            
            // Setup base schema if needed and using in-memory SQLite
            if ($this->driver === 'sqlite' && getenv('DB_DATABASE') === ':memory:') {
                $this->setupTestSchema();
            }
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up any tables created during testing
        if ($this->connection) {
            try {
                // Drop any tables created during the test
                foreach ($this->createdTables as $table) {
                    $this->dropTable($table);
                }
                
                // In SQLite, we can use PRAGMA to clean up
                if ($this->driver === 'sqlite') {
                    $this->connection->getPdo()->exec('PRAGMA optimize');
                }
            } catch (PDOException $e) {
                // Ignore errors during cleanup
            }
        }
        
        parent::tearDown();
    }
    
    /**
     * Create migrations table if it doesn't exist
     */
    protected function createMigrationsTable(): void
    {
        $pdo = $this->connection->getPdo();
        
        switch ($this->driver) {
            case 'sqlite':
                $query = "
                    CREATE TABLE IF NOT EXISTS migrations (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        migration VARCHAR(255) NOT NULL,
                        batch INTEGER NOT NULL
                    )
                ";
                break;
                
            case 'mysql':
            case 'mariadb':
                $query = "
                    CREATE TABLE IF NOT EXISTS migrations (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INT NOT NULL
                    )
                ";
                break;
                
            case 'pgsql':
                $query = "
                    CREATE TABLE IF NOT EXISTS migrations (
                        id SERIAL PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INTEGER NOT NULL
                    )
                ";
                break;
                
            default:
                $query = "
                    CREATE TABLE IF NOT EXISTS migrations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INT NOT NULL
                    )
                ";
                break;
        }
        
        $pdo->exec($query);
    }
    
    /**
     * Setup a minimal test schema for testing
     */
    protected function setupTestSchema(): void
    {
        $tables = $this->getTables();
        
        // Only create tables if they don't exist
        if (!in_array('users', $tables)) {
            $this->connection->execute('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ');
            
            // Insert test user
            $this->connection->execute("
                INSERT INTO users (name, email, password, created_at, updated_at)
                VALUES ('Test User', 'test@example.com', 'password_hash', datetime('now'), datetime('now'))
            ");
        }
        
        if (!in_array('tasks', $tables)) {
            $this->connection->execute('
                CREATE TABLE tasks (
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
            
            // Insert test task
            $this->connection->execute("
                INSERT INTO tasks (title, description, status, priority, user_id, created_at, updated_at)
                VALUES ('Test Task', 'This is a test task', 'pending', 1, 1, datetime('now'), datetime('now'))
            ");
        }
    }
    
    /**
     * Create a table for testing and register it for cleanup
     * 
     * @param string $name Table name
     * @param string $schema Table schema
     * @return bool Success
     */
    protected function createTable(string $name, string $schema): bool
    {
        try {
            $this->connection->execute($schema);
            $this->createdTables[] = $name;
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Drop a table
     * 
     * @param string $name Table name
     * @return bool Success
     */
    protected function dropTable(string $name): bool
    {
        try {
            $this->connection->execute("DROP TABLE IF EXISTS {$name}");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all tables in the database
     *
     * @return array
     */
    protected function getTables(): array
    {
        $tables = [];
        $pdo = $this->connection->getPdo();
        
        switch ($this->driver) {
            case 'sqlite':
                $query = "SELECT name FROM sqlite_master WHERE type='table'";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tables[] = $row['name'];
                }
                break;
                
            case 'mysql':
            case 'mariadb':
                $query = "SHOW TABLES";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                break;
                
            case 'pgsql':
                $query = "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tables[] = $row['tablename'];
                }
                break;
                
            case 'sqlsrv':
                $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tables[] = $row['TABLE_NAME'];
                }
                break;
                
            default:
                throw new \RuntimeException("Getting tables not implemented for driver: {$this->driver}");
        }
        
        return $tables;
    }
    
    /**
     * Get all columns for a table
     *
     * @param string $table
     * @return array
     */
    protected function getColumns(string $table): array
    {
        $columns = [];
        $pdo = $this->connection->getPdo();
        
        switch ($this->driver) {
            case 'sqlite':
                $query = "PRAGMA table_info({$table})";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[$row['name']] = $row['type'];
                }
                break;
                
            case 'mysql':
            case 'mariadb':
                $query = "DESCRIBE {$table}";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[$row['Field']] = $row['Type'];
                }
                break;
                
            case 'pgsql':
                $query = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '{$table}'";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[$row['column_name']] = $row['data_type'];
                }
                break;
                
            case 'sqlsrv':
                $query = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table}'";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
                }
                break;
                
            default:
                throw new \RuntimeException("Getting columns not implemented for driver: {$this->driver}");
        }
        
        return $columns;
    }
} 