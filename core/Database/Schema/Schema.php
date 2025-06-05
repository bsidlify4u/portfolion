<?php

namespace Portfolion\Database\Schema;

use Portfolion\Database\SchemaBuilder;
use PDO;
use PDOException;
use RuntimeException;

class Schema
{
    /**
     * The database connection instance.
     *
     * @var PDO
     */
    protected static $connection;
    
    /**
     * The database driver type.
     *
     * @var string
     */
    protected static $driver;

    /**
     * Get a schema builder instance.
     *
     * @return SchemaBuilder
     */
    protected static function getSchemaBuilder(): SchemaBuilder
    {
        if (!static::$connection) {
            // Load database configuration
            $config = require base_path('config/database.php');
            
            $connection = $config['default'];
            $options = $config['connections'][$connection] ?? null;
            
            if (!$options) {
                throw new RuntimeException("Database connection [{$connection}] not configured.");
            }
            
            static::$driver = $options['driver'] ?? $connection;
            
            // Establish database connection based on the configured driver
            static::$connection = static::createConnection(static::$driver, $options);
        }
        
        return new SchemaBuilder(static::$connection, static::$driver);
    }
    
    /**
     * Create a PDO connection for the specified driver and options.
     *
     * @param string $driver
     * @param array $options
     * @return PDO
     * @throws RuntimeException If the connection fails
     */
    protected static function createConnection(string $driver, array $options): PDO
    {
        try {
            switch ($driver) {
                case 'sqlite':
                    $path = $options['database'];
                    
                    if ($path === ':memory:') {
                        return new PDO("sqlite::memory:");
                    }
                    
                    $fullPath = strpos($path, '/') === 0 ? $path : base_path($path);
                    
                    $directory = dirname($fullPath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    if (!file_exists($fullPath)) {
                        touch($fullPath);
                    }
                    
                    $pdo = new PDO("sqlite:{$fullPath}");
                    
                    // Enable foreign key constraints if specified
                    if ($options['foreign_key_constraints'] ?? false) {
                        $pdo->exec('PRAGMA foreign_keys = ON;');
                    }
                    
                    return $pdo;
                    
                case 'mysql':
                case 'mariadb':
                    $host = $options['host'] ?? 'localhost';
                    $port = $options['port'] ?? '3306';
                    $database = $options['database'];
                    $username = $options['username'] ?? 'root';
                    $password = $options['password'] ?? '';
                    $charset = $options['charset'] ?? 'utf8mb4';
                    $socket = $options['unix_socket'] ?? '';
                    
                    $dsn = "mysql:";
                    
                    if (!empty($socket) && file_exists($socket)) {
                        $dsn .= "unix_socket={$socket};";
                    } else {
                        $dsn .= "host={$host};port={$port};";
                    }
                    
                    $dsn .= "dbname={$database};charset={$charset}";
                    
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Set strict mode if configured
                    if ($options['strict'] ?? true) {
                        $pdo->exec("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
                    }
                    
                    return $pdo;
                    
                case 'pgsql':
                    $host = $options['host'] ?? 'localhost';
                    $port = $options['port'] ?? '5432';
                    $database = $options['database'];
                    $username = $options['username'] ?? 'postgres';
                    $password = $options['password'] ?? '';
                    $schema = $options['search_path'] ?? 'public';
                    
                    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                    
                    if (isset($options['sslmode'])) {
                        $dsn .= ";sslmode={$options['sslmode']}";
                    }
                    
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Set the search path
                    if ($schema) {
                        $pdo->exec("SET search_path TO {$schema}");
                    }
                    
                    return $pdo;
                    
                case 'sqlsrv':
                    $host = $options['host'] ?? 'localhost';
                    $port = $options['port'] ?? '1433';
                    $database = $options['database'];
                    $username = $options['username'] ?? 'sa';
                    $password = $options['password'] ?? '';
                    
                    $dsn = "sqlsrv:Server={$host}";
                    
                    if (!empty($port)) {
                        $dsn .= ",{$port}";
                    }
                    
                    $dsn .= ";Database={$database}";
                    
                    if (!empty($options['charset'])) {
                        $dsn .= ";CharacterSet={$options['charset']}";
                    }
                    
                    if (isset($options['encrypt'])) {
                        $dsn .= ";Encrypt=" . ($options['encrypt'] === 'yes' ? 'true' : 'false');
                    }
                    
                    if (isset($options['trust_server_certificate'])) {
                        $dsn .= ";TrustServerCertificate=" . ($options['trust_server_certificate'] ? 'true' : 'false');
                    }
                    
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    return $pdo;
                    
                case 'oracle':
                case 'oci':
                    $host = $options['host'] ?? 'localhost';
                    $port = $options['port'] ?? '1521';
                    $database = $options['database'] ?? '';
                    $service = $options['service_name'] ?? '';
                    $username = $options['username'] ?? 'system';
                    $password = $options['password'] ?? '';
                    
                    // Build the connection string
                    if (!empty($service)) {
                        $dsn = "oci:dbname=//{$host}:{$port}/{$service}";
                    } elseif (!empty($options['tns'])) {
                        $dsn = "oci:dbname={$options['tns']}";
                    } else {
                        $dsn = "oci:dbname={$host}:{$port}/{$database}";
                    }
                    
                    if (!empty($options['charset'])) {
                        $dsn .= ";charset={$options['charset']}";
                    }
                    
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Set Oracle specific session parameters
                    $pdo->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
                    $pdo->exec("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF'");
                    
                    return $pdo;
                    
                default:
                    throw new RuntimeException("Unsupported database driver: {$driver}");
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new table on the schema.
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    public static function create(string $table, callable $callback): void
    {
        static::getSchemaBuilder()->create($table, $callback);
    }

    /**
     * Drop a table from the schema.
     *
     * @param  string  $table
     * @return void
     */
    public static function drop(string $table): void
    {
        static::getSchemaBuilder()->drop($table);
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string  $table
     * @return void
     */
    public static function dropIfExists(string $table): void
    {
        static::getSchemaBuilder()->drop($table);
    }

    /**
     * Alter a table on the schema.
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    public static function table(string $table, callable $callback): void
    {
        static::getSchemaBuilder()->table($table, $callback);
    }
    
    /**
     * Execute a raw SQL statement on the database.
     *
     * @param  string  $sql
     * @return bool
     */
    public static function statement(string $sql): bool
    {
        try {
            $connection = static::getSchemaBuilder()->getConnection();
            return $connection->exec($sql) !== false;
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to execute SQL statement: " . $e->getMessage(), 0, $e);
        }
    }
} 