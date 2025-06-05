<?php

namespace Portfolion\Database;

use Portfolion\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Base seeder class that all seeders should extend
 */
abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Create a new seeder instance.
     */
    public function __construct()
    {
        $this->container = app();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Seed the given connection from the given path.
     *
     * @param array|string $class
     * @return void
     */
    public function call(array|string $class): void
    {
        if (is_array($class)) {
            foreach ($class as $seeder) {
                $this->callOne($seeder);
            }
            return;
        }

        $this->callOne($class);
    }

    /**
     * Run a single seeder.
     *
     * @param string $class
     * @return void
     */
    protected function callOne(string $class): void
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Seeder class [{$class}] does not exist.");
        }

        $seeder = new $class();
        
        $this->beforeRun($class);
        
        $seeder->run();
        
        $this->afterRun($class);
    }

    /**
     * Before running a seeder.
     *
     * @param string $class
     * @return void
     */
    protected function beforeRun(string $class): void
    {
        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeding:</info> {$class}");
        } else {
            echo "Seeding: {$class}" . PHP_EOL;
        }
    }

    /**
     * After running a seeder.
     *
     * @param string $class
     * @return void
     */
    protected function afterRun(string $class): void
    {
        // Log or perform any actions after running a seeder
    }

    /**
     * Set the console command instance.
     *
     * @param mixed $command
     * @return $this
     */
    public function setCommand($command): self
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Get the database connection instance.
     *
     * @param string|null $connection
     * @return Connection
     */
    protected function connection(?string $connection = null): Connection
    {
        return DB::connection($connection);
    }

    /**
     * Get a query builder for the specified table.
     *
     * @param string $table
     * @param string|null $connection
     * @return QueryBuilder
     */
    protected function table(string $table, ?string $connection = null): QueryBuilder
    {
        return $this->connection($connection)->table($table);
    }
}