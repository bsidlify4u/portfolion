<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use Portfolion\Database\QueryBuilder;
use Portfolion\Database\Connection;
use PDO;

class QueryBuilderTest extends TestCase
{
    protected ?QueryBuilder $queryBuilder;
    protected ?Connection $connection;
    protected string $driver;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Force SQLite in-memory for consistent testing
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        
        $this->connection = new Connection();
        $this->driver = $this->connection->getDriver();
        $this->queryBuilder = new QueryBuilder($this->connection);
        
        // Create a test table based on the database driver
        $createTableSql = $this->getCreateTableSql();
        $this->connection->execute($createTableSql);
        
        // Add some test data
        $this->connection->execute('
            INSERT INTO users (name, email, created_at, updated_at) 
            VALUES (?, ?, ?, ?)',
            ['John Doe', 'john@example.com', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Get the appropriate CREATE TABLE SQL for the current database driver
     *
     * @return string SQL statement
     */
    protected function getCreateTableSql(): string
    {
        switch ($this->driver) {
            case 'sqlite':
                return '
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT,
                        email TEXT,
                        created_at TEXT,
                        updated_at TEXT
                    )
                ';
                
            case 'mysql':
            case 'mariadb':
                return '
                    CREATE TABLE IF NOT EXISTS users (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255),
                        email VARCHAR(255),
                        created_at DATETIME,
                        updated_at DATETIME
                    )
                ';
                
            case 'pgsql':
                return '
                    CREATE TABLE IF NOT EXISTS users (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(255),
                        email VARCHAR(255),
                        created_at TIMESTAMP,
                        updated_at TIMESTAMP
                    )
                ';
                
            default:
                // Generic SQL that should work for most databases
                return '
                    CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255),
                        email VARCHAR(255),
                        created_at DATETIME,
                        updated_at DATETIME
                    )
                ';
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up the test table
        $this->connection->execute('DROP TABLE IF EXISTS users');
        
        parent::tearDown();
    }
    
    public function testTableMethod()
    {
        $result = $this->queryBuilder->table('users');
        $this->assertInstanceOf(QueryBuilder::class, $result);
    }
    
    public function testWhereClause()
    {
        $result = $this->queryBuilder->table('users')->where('id', '=', 1);
        $this->assertInstanceOf(QueryBuilder::class, $result);
    }
    
    public function testGetConnection()
    {
        $connection = $this->queryBuilder->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
    }
    
    public function testEnsureConnected()
    {
        $result = $this->queryBuilder->ensureConnected();
        $this->assertTrue($result);
    }
    
    public function testGet()
    {
        $users = $this->queryBuilder->table('users')->get();
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]['name']);
    }
    
    public function testWhere()
    {
        $users = $this->queryBuilder->table('users')
            ->where('name', '=', 'John Doe')
            ->get();
            
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        
        $users = $this->queryBuilder->table('users')
            ->where('name', '=', 'Jane Doe')
            ->get();
            
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
    }
} 