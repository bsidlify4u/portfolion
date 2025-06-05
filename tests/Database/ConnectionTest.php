<?php

namespace Tests\Database;

use Tests\TestCase;
use Portfolion\Database\Connection;
use PDO;
use PDOException;

class ConnectionTest extends TestCase
{
    /**
     * @var string The database driver being used
     */
    protected string $driver;
    
    /**
     * @var Connection
     */
    protected Connection $connection;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a fresh connection for each test
        $this->connection = new Connection();
        $this->driver = $this->connection->getDriver();
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Clean up any test tables that may have been created
        try {
            $this->connection->execute('DROP TABLE IF EXISTS test_execute');
            $this->connection->execute('DROP TABLE IF EXISTS test_select');
            $this->connection->execute('DROP TABLE IF EXISTS test_insert');
            $this->connection->execute('DROP TABLE IF EXISTS test_transaction');
        } catch (PDOException $e) {
            // Ignore errors during cleanup
        }
        
        parent::tearDown();
    }
    
    /**
     * Test that the connection can be established with the default driver
     */
    public function testConnectionCanBeEstablished()
    {
        $pdo = $this->connection->getPdo();
        
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertTrue($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION);
        
        // Test a simple query
        $stmt = $pdo->query('SELECT 1');
        $result = $stmt->fetchColumn();
        $this->assertEquals(1, $result);
    }
    
    /**
     * Test that the driver name is correctly returned
     */
    public function testGetDriverReturnsCorrectDriver()
    {
        $driver = $this->connection->getDriver();
        
        // The driver should be a non-empty string
        $this->assertIsString($driver);
        $this->assertNotEmpty($driver);
        
        // Since we're using MySQL, we expect 'mysql' as the driver
        $this->assertEquals('mysql', $driver);
    }
    
    /**
     * Test the execute method for running queries
     */
    public function testExecuteRunsQueries()
    {
        // Create a test table using driver-specific SQL
        $createTableSql = $this->getCreateTableSql('test_execute', [
            'id' => 'INTEGER',
            'name' => 'TEXT'
        ]);
        
        $result = $this->connection->execute($createTableSql);
        $this->assertTrue($result);
        
        // Insert a row
        $result = $this->connection->execute('INSERT INTO test_execute (id, name) VALUES (?, ?)', [1, 'Test']);
        $this->assertTrue($result);
        
        // Verify the row was inserted
        $rows = $this->connection->select('SELECT * FROM test_execute');
        $this->assertCount(1, $rows);
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals('Test', $rows[0]['name']);
    }
    
    /**
     * Test the select method for retrieving data
     */
    public function testSelectRetrievesData()
    {
        // Create a test table using driver-specific SQL
        $createTableSql = $this->getCreateTableSql('test_select', [
            'id' => 'INTEGER',
            'name' => 'TEXT'
        ]);
        
        $this->connection->execute($createTableSql);
        
        // Insert multiple rows
        $this->connection->execute('INSERT INTO test_select (id, name) VALUES (?, ?)', [1, 'One']);
        $this->connection->execute('INSERT INTO test_select (id, name) VALUES (?, ?)', [2, 'Two']);
        $this->connection->execute('INSERT INTO test_select (id, name) VALUES (?, ?)', [3, 'Three']);
        
        // Select all rows
        $rows = $this->connection->select('SELECT * FROM test_select ORDER BY id');
        $this->assertCount(3, $rows);
        $this->assertEquals('One', $rows[0]['name']);
        $this->assertEquals('Two', $rows[1]['name']);
        $this->assertEquals('Three', $rows[2]['name']);
        
        // Select with a WHERE clause
        $rows = $this->connection->select('SELECT * FROM test_select WHERE id > ?', [1]);
        $this->assertCount(2, $rows);
        $this->assertEquals('Two', $rows[0]['name']);
        $this->assertEquals('Three', $rows[1]['name']);
    }
    
    /**
     * Test the insert method
     */
    public function testInsertAddsData()
    {
        // Create a test table with autoincrement using driver-specific SQL
        $createTableSql = $this->getCreateTableSql('test_insert', [
            'id' => 'PRIMARY_KEY_AUTO',
            'name' => 'TEXT'
        ]);
        
        $this->connection->execute($createTableSql);
        
        // Insert a row
        $id = $this->connection->insert('test_insert', [
            'name' => 'Test Insert'
        ]);
        
        // The insert method should return the last insert ID
        $this->assertIsNumeric($id);
        
        // Verify the row was inserted
        $rows = $this->connection->select('SELECT * FROM test_insert');
        $this->assertCount(1, $rows);
        $this->assertEquals('Test Insert', $rows[0]['name']);
    }
    
    /**
     * Test transaction support
     */
    public function testTransactionSupport()
    {
        // Create a test table using driver-specific SQL
        $createTableSql = $this->getCreateTableSql('test_transaction', [
            'id' => 'INTEGER',
            'name' => 'TEXT'
        ]);
        
        $this->connection->execute($createTableSql);
        
        // Start a transaction
        $this->assertTrue($this->connection->beginTransaction());
        
        // Insert a row
        $this->connection->execute('INSERT INTO test_transaction (id, name) VALUES (?, ?)', [1, 'Transaction Test']);
        
        // Verify the row exists within the transaction
        $rows = $this->connection->select('SELECT * FROM test_transaction');
        $this->assertCount(1, $rows);
        
        // Rollback the transaction
        $this->assertTrue($this->connection->rollBack());
        
        // Verify the row no longer exists
        $rows = $this->connection->select('SELECT * FROM test_transaction');
        $this->assertCount(0, $rows);
        
        // Test commit
        $this->assertTrue($this->connection->beginTransaction());
        $this->connection->execute('INSERT INTO test_transaction (id, name) VALUES (?, ?)', [2, 'Committed']);
        $this->assertTrue($this->connection->commit());
        
        // Verify the row was committed
        $rows = $this->connection->select('SELECT * FROM test_transaction');
        $this->assertCount(1, $rows);
        $this->assertEquals('Committed', $rows[0]['name']);
    }
    
    /**
     * Get the appropriate CREATE TABLE SQL for the current database driver
     *
     * @param string $tableName Name of the table to create
     * @param array $columns Column definitions
     * @return string SQL statement
     */
    protected function getCreateTableSql(string $tableName, array $columns): string
    {
        $columnDefs = [];
        
        foreach ($columns as $name => $type) {
            $columnDefs[] = $this->getColumnDefinition($name, $type);
        }
        
        return "CREATE TABLE IF NOT EXISTS {$tableName} (" . implode(', ', $columnDefs) . ")";
    }
    
    /**
     * Get column definition based on database driver
     *
     * @param string $name Column name
     * @param string $type Column type
     * @return string Column definition
     */
    protected function getColumnDefinition(string $name, string $type): string
    {
        if ($type === 'PRIMARY_KEY_AUTO') {
            switch ($this->driver) {
                case 'sqlite':
                    return "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
                case 'mysql':
                case 'mariadb':
                    return "{$name} INT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
                case 'pgsql':
                    return "{$name} SERIAL PRIMARY KEY";
                default:
                    return "{$name} INT AUTO_INCREMENT PRIMARY KEY";
            }
        }
        
        if ($type === 'TEXT') {
            switch ($this->driver) {
                case 'sqlite':
                    return "{$name} TEXT";
                case 'mysql':
                case 'mariadb':
                case 'pgsql':
                    return "{$name} VARCHAR(255)";
                default:
                    return "{$name} VARCHAR(255)";
            }
        }
        
        if ($type === 'INTEGER') {
            switch ($this->driver) {
                case 'sqlite':
                    return "{$name} INTEGER";
                case 'mysql':
                case 'mariadb':
                    return "{$name} INT";
                case 'pgsql':
                    return "{$name} INTEGER";
                default:
                    return "{$name} INT";
            }
        }
        
        // Default: just use the type as provided
        return "{$name} {$type}";
    }
} 