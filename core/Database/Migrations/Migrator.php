<?php

namespace Portfolion\Database\Migrations;

use Exception;
use Portfolion\Database\Connection;
use Portfolion\Database\DB;
use Portfolion\Database\Migration;
use PDO;
use RuntimeException;

class Migrator
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The path to the migrations directory.
     *
     * @var string
     */
    protected string $path;

    /**
     * The name of the migrations table.
     *
     * @var string
     */
    protected string $table = 'migrations';

    /**
     * Create a new migrator instance.
     *
     * @param Connection|null $connection
     * @param string|null $path
     */
    public function __construct(?Connection $connection = null, ?string $path = null)
    {
        $this->connection = $connection ?? DB::connection();
        $this->path = $path ?? database_path('migrations');
    }

    /**
     * Run the pending migrations.
     *
     * @param int|null $steps The maximum number of migrations to run
     * @return array The migrations that were run
     */
    public function run(?int $steps = null): array
    {
        $this->ensureMigrationsTableExists();

        $files = $this->getMigrationFiles();
        $ran = $this->getRanMigrations();
        
        $migrations = array_diff($files, $ran);
        if (empty($migrations)) {
            return [];
        }

        // Sort migrations by name (which includes the timestamp)
        sort($migrations);

        // Apply step limit if specified
        if ($steps !== null) {
            $migrations = array_slice($migrations, 0, $steps);
        }

        $migrated = [];
        
        foreach ($migrations as $migration) {
            $this->runMigration($migration);
            $migrated[] = $migration;
        }

        return $migrated;
    }

    /**
     * Rollback the last batch of migrations.
     *
     * @param int|null $steps The number of migration batches to rollback
     * @return array The migrations that were rolled back
     */
    public function rollback(?int $steps = null): array
    {
        $this->ensureMigrationsTableExists();

        $query = "SELECT migration, batch FROM {$this->table} ORDER BY batch DESC, migration DESC";
        $migrations = $this->connection->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($migrations)) {
            return [];
        }

        // Group migrations by batch
        $batches = [];
        foreach ($migrations as $migration) {
            $batch = $migration['batch'];
            if (!isset($batches[$batch])) {
                $batches[$batch] = [];
            }
            $batches[$batch][] = $migration['migration'];
        }

        // Get the batches to rollback
        $batchNumbers = array_keys($batches);
        $stepsToRollback = $steps ?? 1;
        $batchesToRollback = array_slice($batchNumbers, 0, $stepsToRollback);

        $rolledBack = [];
        
        foreach ($batchesToRollback as $batch) {
            foreach ($batches[$batch] as $migration) {
                $this->rollbackMigration($migration);
                $rolledBack[] = $migration;
            }
        }

        return $rolledBack;
    }

    /**
     * Reset all migrations.
     *
     * @return array The migrations that were rolled back
     */
    public function reset(): array
    {
        $this->ensureMigrationsTableExists();

        $query = "SELECT migration FROM {$this->table} ORDER BY migration DESC";
        $migrations = $this->connection->query($query)->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($migrations)) {
            return [];
        }

        $rolledBack = [];
        
        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }

        return $rolledBack;
    }

    /**
     * Run a specific migration.
     *
     * @param string $file
     * @return void
     */
    protected function runMigration(string $file): void
    {
        $migration = $this->getMigrationInstance($file);

        $this->note("Migrating: {$file}");

        $startTime = microtime(true);

        try {
            $migration->up();

            $runTime = round(microtime(true) - $startTime, 2);
            $this->note("Migrated: {$file} ({$runTime} seconds)");

            // Record the migration
            $batch = $this->getNextBatchNumber();
            $this->connection->table($this->table)->insert([
                'migration' => $file,
                'batch' => $batch,
            ]);
        } catch (Exception $e) {
            $this->note("Error in {$file}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rollback a specific migration.
     *
     * @param string $file
     * @return void
     */
    protected function rollbackMigration(string $file): void
    {
        $migration = $this->getMigrationInstance($file);

        $this->note("Rolling back: {$file}");

        $startTime = microtime(true);

        try {
            $migration->down();

            $runTime = round(microtime(true) - $startTime, 2);
            $this->note("Rolled back: {$file} ({$runTime} seconds)");

            // Remove the migration record
            $this->connection->table($this->table)
                ->where('migration', $file)
                ->delete();
        } catch (Exception $e) {
            $this->note("Error rolling back {$file}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get an instance of a migration.
     *
     * @param string $file
     * @return Migration
     */
    protected function getMigrationInstance(string $file): Migration
    {
        $class = $this->getMigrationClass($file);
        $path = $this->path . '/' . $file . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException("Migration file not found: {$path}");
        }

        require_once $path;

        if (!class_exists($class)) {
            throw new RuntimeException("Migration class not found: {$class}");
        }

        return new $class();
    }

    /**
     * Get the class name from a migration file.
     *
     * @param string $file
     * @return string
     */
    protected function getMigrationClass(string $file): string
    {
        // Remove timestamp prefix from the file name
        $parts = explode('_', $file, 5);
        
        if (count($parts) === 5) {
            return $parts[4];
        }
        
        return $file;
    }

    /**
     * Get the list of migration files.
     *
     * @return array
     */
    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = scandir($this->path);
        $migrations = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $name = pathinfo($file, PATHINFO_FILENAME);
            $migrations[] = $name;
        }

        return $migrations;
    }

    /**
     * Get the list of migrations that have already been run.
     *
     * @return array
     */
    protected function getRanMigrations(): array
    {
        try {
            $query = "SELECT migration FROM {$this->table}";
            return $this->connection->query($query)->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get the next batch number.
     *
     * @return int
     */
    protected function getNextBatchNumber(): int
    {
        try {
            $query = "SELECT MAX(batch) FROM {$this->table}";
            $batch = $this->connection->query($query)->fetchColumn();
            return $batch ? $batch + 1 : 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Ensure the migrations table exists.
     *
     * @return void
     */
    protected function ensureMigrationsTableExists(): void
    {
        try {
            $this->connection->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (Exception $e) {
            $this->createMigrationsTable();
        }
    }

    /**
     * Create the migrations table.
     *
     * @return void
     */
    protected function createMigrationsTable(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        
        $schema->create($this->table, function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Output a note to the console.
     *
     * @param string $message
     * @return void
     */
    protected function note(string $message): void
    {
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        }
    }
} 