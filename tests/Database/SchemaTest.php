<?php

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use Portfolion\Database\Connection;
use Portfolion\Database\Schema\Schema;
use Portfolion\Database\Schema\Blueprint;
use PDO;
use PDOException;

class SchemaTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $connection;
    
    /**
     * @var string
     */
    protected $driver;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $this->connection = new Connection();
            $this->driver = $this->connection->getDriver();
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test tables
        try {
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_schema');
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_schema_columns');
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_schema_indexes');
        } catch (PDOException $e) {
            // Ignore errors during cleanup
        }
        
        parent::tearDown();
    }
    
    /**
     * Test creating a table with Schema
     */
    public function testCreateTable()
    {
        Schema::create('test_schema', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Verify the table exists
        $tables = $this->getTables();
        $this->assertContains('test_schema', $tables);
        
        // Verify the columns exist
        $columns = $this->getColumns('test_schema');
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('description', $columns);
        $this->assertArrayHasKey('created_at', $columns);
        $this->assertArrayHasKey('updated_at', $columns);
    }
    
    /**
     * Test dropping a table with Schema
     */
    public function testDropTable()
    {
        // Create a table first
        Schema::create('test_schema', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        
        // Verify the table exists
        $tables = $this->getTables();
        $this->assertContains('test_schema', $tables);
        
        // Drop the table
        Schema::drop('test_schema');
        
        // Verify the table no longer exists
        $tables = $this->getTables();
        $this->assertNotContains('test_schema', $tables);
    }
    
    /**
     * Test creating a table with different column types
     */
    public function testColumnTypes()
    {
        Schema::create('test_schema_columns', function (Blueprint $table) {
            $table->id();
            $table->string('string_col', 100);
            $table->text('text_col');
            $table->integer('int_col');
            $table->bigInteger('bigint_col');
            $table->boolean('bool_col');
            $table->date('date_col');
            $table->dateTime('datetime_col');
            $table->timestamp('timestamp_col');
        });
        
        // Verify the table exists
        $tables = $this->getTables();
        $this->assertContains('test_schema_columns', $tables);
        
        // Verify all columns exist
        $columns = $this->getColumns('test_schema_columns');
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('string_col', $columns);
        $this->assertArrayHasKey('text_col', $columns);
        $this->assertArrayHasKey('int_col', $columns);
        $this->assertArrayHasKey('bigint_col', $columns);
        $this->assertArrayHasKey('bool_col', $columns);
        $this->assertArrayHasKey('date_col', $columns);
        $this->assertArrayHasKey('datetime_col', $columns);
        $this->assertArrayHasKey('timestamp_col', $columns);
    }
    
    /**
     * Test creating a table with indexes
     */
    public function testIndexes()
    {
        Schema::create('test_schema_indexes', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('email')->unique(); // Unique index
            $table->string('name');
            $table->integer('category_id');
            $table->index('category_id'); // Regular index
        });
        
        // Verify the table exists
        $tables = $this->getTables();
        $this->assertContains('test_schema_indexes', $tables);
        
        // Verify the columns exist
        $columns = $this->getColumns('test_schema_indexes');
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('email', $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('category_id', $columns);
        
        // We can't easily test for indexes in a database-agnostic way
        // as each database returns index information differently
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
                $this->markTestSkipped("Getting tables not implemented for driver: {$this->driver}");
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
                $this->markTestSkipped("Getting columns not implemented for driver: {$this->driver}");
        }
        
        return $columns;
    }
} 