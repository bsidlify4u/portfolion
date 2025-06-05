<?php

namespace Portfolion\Database\Migrations;

use InvalidArgumentException;
use RuntimeException;

class MigrationCreator
{
    /**
     * The path to the migrations directory.
     *
     * @var string
     */
    protected string $migrationsPath;

    /**
     * Create a new migration creator instance.
     *
     * @param string|null $migrationsPath
     */
    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? database_path('migrations');
    }

    /**
     * Create a new migration file.
     *
     * @param string $name
     * @param string|null $table
     * @param bool $create
     * @return string
     */
    public function create(string $name, ?string $table = null, bool $create = false): string
    {
        $this->ensureMigrationDirectoryExists();

        $name = $this->getClassName($name);
        $path = $this->getPath($name);

        // Check if migration already exists
        if (file_exists($path)) {
            throw new RuntimeException("Migration file already exists: {$path}");
        }

        // Create migration file
        file_put_contents($path, $this->getStub($name, $table, $create));

        return $path;
    }

    /**
     * Get the class name for a migration name.
     *
     * @param string $name
     * @return string
     */
    protected function getClassName(string $name): string
    {
        // Format the name: create_users_table -> CreateUsersTable
        $name = str_replace(' ', '_', ucwords(str_replace('_', ' ', $name)));
        
        return date('Y_m_d_His') . '_' . $name;
    }

    /**
     * Get the full path for a migration file.
     *
     * @param string $name
     * @return string
     */
    protected function getPath(string $name): string
    {
        return $this->migrationsPath . '/' . $name . '.php';
    }

    /**
     * Get the migration stub content.
     *
     * @param string $className
     * @param string|null $table
     * @param bool $create
     * @return string
     */
    protected function getStub(string $className, ?string $table, bool $create): string
    {
        // Extract just the class name without timestamp
        $parts = explode('_', $className, 5);
        $classNameWithoutTimestamp = $parts[4] ?? $className;

        $stub = file_get_contents($this->getStubPath($table, $create));

        // Replace placeholders
        $stub = str_replace('{{ class }}', $classNameWithoutTimestamp, $stub);
        
        if ($table) {
            $stub = str_replace('{{ table }}', $table, $stub);
        }

        return $stub;
    }

    /**
     * Get the path to the appropriate stub.
     *
     * @param string|null $table
     * @param bool $create
     * @return string
     */
    protected function getStubPath(?string $table, bool $create): string
    {
        if ($table === null) {
            return __DIR__ . '/stubs/blank.stub';
        }

        return $create 
            ? __DIR__ . '/stubs/create.stub'
            : __DIR__ . '/stubs/update.stub';
    }

    /**
     * Ensure the migrations directory exists.
     *
     * @return void
     */
    protected function ensureMigrationDirectoryExists(): void
    {
        if (!is_dir($this->migrationsPath)) {
            if (!mkdir($this->migrationsPath, 0755, true) && !is_dir($this->migrationsPath)) {
                throw new RuntimeException("Failed to create directory: {$this->migrationsPath}");
            }
        }
    }
} 