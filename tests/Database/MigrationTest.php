<?php

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use Portfolion\Database\Connection;
use Portfolion\Database\Migration;
use Portfolion\Console\Commands\MigrationCommand;
use PDO;
use PDOException;

// Create a test migration class
class TestMigration extends Migration
{
    public function up(): void
    {
        $connection = $this->getConnection();
        $pdo = $connection->getPdo();
        
        $pdo->exec("
            CREATE TABLE test_migration (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )
        ");
    }
    
    public function down(): void
    {
        $connection = $this->getConnection();
        $pdo = $connection->getPdo();
        
        $pdo->exec("DROP TABLE IF EXISTS test_migration");
    }
}

class MigrationTest extends TestCase
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
     * @var MigrationCommand
     */
    protected $migrationCommand;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $this->connection = new Connection();
            $this->driver = $this->connection->getDriver();
            $this->migrationCommand = new MigrationCommand();
            
            // Ensure migrations table exists
            $this->createMigrationsTable();
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test tables
        try {
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_migration');
            $this->connection->getPdo()->exec('DELETE FROM migrations WHERE migration LIKE "%test_migration%"');
        } catch (PDOException $e) {
            // Ignore errors during cleanup
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
     * Test running a migration
     */
    public function testRunMigration()
    {
        // Create a test migration
        $migration = new TestMigration();
        
        // Run the migration
        $migration->up();
        
        // Verify the table was created
        $tables = $this->getTables();
        $this->assertContains('test_migration', $tables);
    }
    
    /**
     * Test rolling back a migration
     */
    public function testRollbackMigration()
    {
        // Create a test migration
        $migration = new TestMigration();
        
        // Run the migration
        $migration->up();
        
        // Verify the table was created
        $tables = $this->getTables();
        $this->assertContains('test_migration', $tables);
        
        // Roll back the migration
        $migration->down();
        
        // Verify the table was dropped
        $tables = $this->getTables();
        $this->assertNotContains('test_migration', $tables);
    }
    
    /**
     * Test recording a migration in the migrations table
     */
    public function testRecordMigration()
    {
        $pdo = $this->connection->getPdo();
        
        // Get the current batch number
        $query = "SELECT MAX(batch) as last_batch FROM migrations";
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $batch = (int) ($result['last_batch'] ?? 0) + 1;
        
        // Record a migration
        $migration = "2023_01_01_000000_test_migration";
        $query = "INSERT INTO migrations (migration, batch) VALUES (?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$migration, $batch]);
        
        // Verify the migration was recorded
        $query = "SELECT * FROM migrations WHERE migration = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$migration]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($result);
        $this->assertEquals($migration, $result['migration']);
        $this->assertEquals($batch, $result['batch']);
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
} 