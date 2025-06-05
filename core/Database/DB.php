<?php

namespace Portfolion\Database;

use PDO;

/**
 * Database facade class
 */
class DB
{
    /**
     * The database connection instance
     */
    protected static ?Connection $instance = null;
    
    /**
     * Get the database connection instance
     */
    public static function connection(): Connection
    {
        if (static::$instance === null) {
            static::$instance = new Connection();
        }
        
        return static::$instance;
    }
    
    /**
     * Get the PDO instance
     */
    public static function getPdo(): PDO
    {
        return static::connection()->getPdo();
    }
    
    /**
     * Execute a raw SQL query
     */
    public static function execute(string $query, array $params = []): bool
    {
        return static::connection()->execute($query, $params);
    }
    
    /**
     * Run a select query and fetch all results
     */
    public static function select(string $query, array $params = []): array
    {
        return static::connection()->select($query, $params);
    }
    
    /**
     * Run an insert query
     */
    public static function insert(string $table, array $data)
    {
        return static::connection()->insert($table, $data);
    }
    
    /**
     * Begin a transaction
     */
    public static function beginTransaction(): bool
    {
        return static::connection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public static function commit(): bool
    {
        return static::connection()->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public static function rollBack(): bool
    {
        return static::connection()->rollBack();
    }
} 