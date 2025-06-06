<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;
use Portfolion\Database\Connection;
use Portfolion\Database\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends Command
{
    /**
     * Command name
     */
    protected string $name = 'migrate';
    
    /**
     * Command description
     */
    protected string $description = 'Run database migrations';
    
    /**
     * Database connection
     */
    protected ?Connection $connection = null;
    
    /**
     * Run the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 if everything went fine, or an exit code
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        // Convert Symfony Console input to array of arguments
        $args = [];
        foreach ($input->getOptions() as $option => $value) {
            if ($value === true) {
                $args[] = "--{$option}";
            } elseif ($value !== false && $value !== null) {
                $args[] = "--{$option}={$value}";
            }
        }
        
        return $this->execute($args);
    }
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        try {
            // Get database connection
            $this->connection = new Connection();
            
            // Check for command options
            if (in_array('--fresh', $args)) {
                $this->fresh();
                $this->info('Database tables have been dropped.');
            } elseif (in_array('--rollback', $args)) {
                $this->rollback();
                $this->info('Last batch of migrations rolled back.');
                return 0;
            } elseif (in_array('--reset', $args)) {
                $this->reset();
                $this->info('All migrations have been reset.');
                return 0;
            }
            
            // Run migrations
            $this->migrate();
            
            $this->info('Migrations completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Run migrations
     */
    protected function migrate(): void
    {
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
        
        // Get all migration files
        $migrations = $this->getMigrationFiles();
        
        // Get completed migrations
        $completedMigrations = $this->getCompletedMigrations();
        
        // Determine which migrations to run
        $pendingMigrations = array_diff($migrations, $completedMigrations);
        
        if (empty($pendingMigrations)) {
            $this->info('Nothing to migrate.');
            return;
        }
        
        $this->line('Running migrations:');
        
        // Run each pending migration
        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration);
            $this->line('  - ' . $migration);
        }
    }
    
    /**
     * Rollback the last batch of migrations
     */
    protected function rollback(): void
    {
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
        
        // Get the last batch number
        $lastBatch = $this->getLastBatchNumber();
        
        if ($lastBatch === 0) {
            $this->info('Nothing to rollback.');
            return;
        }
        
        // Get migrations from the last batch
        $lastBatchMigrations = $this->getMigrationsForBatch($lastBatch);
        
        if (empty($lastBatchMigrations)) {
            $this->info('Nothing to rollback.');
            return;
        }
        
        $this->line('Rolling back:');
        
        // Roll back each migration in reverse order
        foreach (array_reverse($lastBatchMigrations) as $migration) {
            $this->rollbackMigration($migration);
            $this->line('  - ' . $migration);
        }
    }
    
    /**
     * Reset all migrations
     */
    protected function reset(): void
    {
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
        
        // Get all completed migrations
        $completedMigrations = $this->getCompletedMigrations();
        
        if (empty($completedMigrations)) {
            $this->info('Nothing to reset.');
            return;
        }
        
        $this->line('Resetting migrations:');
        
        // Roll back each migration in reverse order
        foreach (array_reverse($completedMigrations) as $migration) {
            $this->rollbackMigration($migration);
            $this->line('  - ' . $migration);
        }
    }
    
    /**
     * Drop all tables and re-run migrations
     */
    protected function fresh(): void
    {
        // Get all tables
        $tables = $this->getAllTables();
        
        // Drop all tables except migrations
        foreach ($tables as $table) {
            if ($table !== 'migrations') {
                $this->connection->getPdo()->exec('DROP TABLE IF EXISTS ' . $table);
            }
        }
    }
    
    /**
     * Create migrations table if it doesn't exist
     */
    protected function createMigrationsTable(): void
    {
        $driver = $this->connection->getDriver();
        
        // Use appropriate SQL syntax based on the database driver
        switch ($driver) {
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
                
            case 'sqlsrv':
                $query = "
                    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='migrations' AND xtype='U')
                    CREATE TABLE migrations (
                        id INT IDENTITY(1,1) PRIMARY KEY,
                        migration NVARCHAR(255) NOT NULL,
                        batch INT NOT NULL
                    )
                ";
                break;
                
            case 'oci':
                // Oracle requires a sequence and trigger for auto-incrementing
                $query = "
                    BEGIN
                        EXECUTE IMMEDIATE 'CREATE SEQUENCE migrations_seq START WITH 1 INCREMENT BY 1';
                        EXECUTE IMMEDIATE '
                            CREATE TABLE migrations (
                                id NUMBER(10) PRIMARY KEY,
                                migration VARCHAR2(255) NOT NULL,
                                batch NUMBER(10) NOT NULL
                            )
                        ';
                        EXECUTE IMMEDIATE '
                            CREATE OR REPLACE TRIGGER migrations_trigger
                            BEFORE INSERT ON migrations
                            FOR EACH ROW
                            BEGIN
                                SELECT migrations_seq.NEXTVAL INTO :new.id FROM dual;
                            END;
                        ';
                    EXCEPTION
                        WHEN OTHERS THEN
                            IF SQLCODE = -955 OR SQLCODE = -1430 OR SQLCODE = -2260 OR SQLCODE = -942 THEN
                                NULL; -- Table or sequence already exists
                            ELSE
                                RAISE;
                            END IF;
                    END;
                ";
                break;
                
            case 'ibm':
            case 'db2':
                $query = "
                    CREATE TABLE migrations (
                        id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INTEGER NOT NULL
                    )
                ";
                break;
                
            default:
                // Generic fallback
                $query = "
                    CREATE TABLE IF NOT EXISTS migrations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INT NOT NULL
                    )
                ";
                break;
        }
        
        try {
            $this->connection->getPdo()->exec($query);
        } catch (\PDOException $e) {
            // Check if the error is because the table already exists
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'duplicate') === false) {
                throw $e;
            }
            // Otherwise, the table exists, which is fine
        }
    }
    
    /**
     * Get all migration files
     *
     * @return array
     */
    protected function getMigrationFiles(): array
    {
        $migrationsPath = 'database/migrations';
        $migrations = [];
        
        if (is_dir($migrationsPath)) {
            $files = scandir($migrationsPath);
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($migrationsPath . '/' . $file)) {
                    $migrations[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        
        sort($migrations);
        
        return $migrations;
    }
    
    /**
     * Get completed migrations
     *
     * @return array
     */
    protected function getCompletedMigrations(): array
    {
        $query = "SELECT migration FROM migrations";
        $stmt = $this->connection->getPdo()->query($query);
        
        $migrations = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $migrations[] = $row['migration'];
        }
        
        return $migrations;
    }
    
    /**
     * Get the last batch number
     *
     * @return int
     */
    protected function getLastBatchNumber(): int
    {
        $query = "SELECT MAX(batch) as last_batch FROM migrations";
        $stmt = $this->connection->getPdo()->query($query);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int) ($result['last_batch'] ?? 0);
    }
    
    /**
     * Get migrations for a specific batch
     *
     * @param int $batch
     * @return array
     */
    protected function getMigrationsForBatch(int $batch): array
    {
        $query = "SELECT migration FROM migrations WHERE batch = ?";
        $stmt = $this->connection->getPdo()->prepare($query);
        $stmt->execute([$batch]);
        
        $migrations = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $migrations[] = $row['migration'];
        }
        
        return $migrations;
    }
    
    /**
     * Run a migration
     *
     * @param string $migration
     */
    protected function runMigration(string $migration): void
    {
        // Include the migration file
        $migrationsPath = 'database/migrations';
        $file = $migrationsPath . '/' . $migration . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        // Get the class name from the file
        $className = 'Database\\Migrations\\' . $this->getClassNameFromFilename($migration);
        
        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: {$className}");
        }
        
        // Create an instance of the migration
        $instance = new $className();
        
        if (!$instance instanceof Migration) {
            throw new \RuntimeException("Migration class must extend Migration: {$className}");
        }
        
        // Run the migration
        $instance->up();
        
        // Record the migration
        $batch = $this->getLastBatchNumber() + 1;
        $query = "INSERT INTO migrations (migration, batch) VALUES (?, ?)";
        $stmt = $this->connection->getPdo()->prepare($query);
        $stmt->execute([$migration, $batch]);
    }
    
    /**
     * Rollback a migration
     *
     * @param string $migration
     */
    protected function rollbackMigration(string $migration): void
    {
        // Include the migration file
        $migrationsPath = 'database/migrations';
        $file = $migrationsPath . '/' . $migration . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        // Get the class name from the file
        $className = 'Database\\Migrations\\' . $this->getClassNameFromFilename($migration);
        
        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: {$className}");
        }
        
        // Create an instance of the migration
        $instance = new $className();
        
        if (!$instance instanceof Migration) {
            throw new \RuntimeException("Migration class must extend Migration: {$className}");
        }
        
        // Run the down method
        $instance->down();
        
        // Remove the migration record
        $query = "DELETE FROM migrations WHERE migration = ?";
        $stmt = $this->connection->getPdo()->prepare($query);
        $stmt->execute([$migration]);
    }
    
    /**
     * Get class name from filename
     *
     * @param string $filename
     * @return string
     */
    protected function getClassNameFromFilename(string $filename): string
    {
        // Remove timestamp prefix (e.g., 2021_01_01_000000_)
        $parts = explode('_', $filename, 5);
        
        if (count($parts) >= 5) {
            $className = $parts[4];
        } else {
            $className = end($parts);
        }
        
        // Convert to camel case
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));
        
        return $className;
    }
    
    /**
     * Get all tables in the database
     *
     * @return array
     */
    protected function getAllTables(): array
    {
        $driver = $this->connection->getDriver();
        $pdo = $this->connection->getPdo();
        $tables = [];
        
        try {
            switch ($driver) {
                case 'sqlite':
                    $query = "SELECT name FROM sqlite_master WHERE type='table'";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $tables[] = $row['name'];
                    }
                    break;
                    
                case 'mysql':
                case 'mariadb':
                    $query = "SHOW TABLES";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                        $tables[] = $row[0];
                    }
                    break;
                    
                case 'pgsql':
                    $query = "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $tables[] = $row['tablename'];
                    }
                    break;
                    
                case 'sqlsrv':
                    $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $tables[] = $row['TABLE_NAME'];
                    }
                    break;
                    
                case 'oci':
                    $query = "SELECT table_name FROM user_tables";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $tables[] = $row['TABLE_NAME'];
                    }
                    break;
                    
                case 'ibm':
                case 'db2':
                    $query = "SELECT tabname FROM syscat.tables WHERE tabschema = CURRENT_SCHEMA AND type = 'T'";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $tables[] = $row['TABNAME'];
                    }
                    break;
                    
                default:
                    // Try a generic approach using information_schema
                    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $tables[] = $row['table_name'];
                    }
                    break;
            }
        } catch (\PDOException $e) {
            $this->error("Error fetching tables: " . $e->getMessage());
        }
        
        return $tables;
    }
} 