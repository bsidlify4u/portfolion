<?php

namespace Portfolion\Database;

use PDO;
use PDOException;
use Portfolion\Config\Config;

class Connection
{
    /**
     * @var PDO The PDO connection instance
     */
    protected PDO $pdo;
    
    /**
     * @var array The database configuration
     */
    protected array $config;
    
    /**
     * @var string The database driver
     */
    protected string $driver;
    
    /**
     * Create a new database connection instance
     */
    public function __construct(?string $driver = null, ?array $config = null)
    {
        $this->config = $config ?? config('database', []);
        $this->driver = $driver ?? $this->config['default'] ?? 'mysql';
        
        $this->connect();
    }
    
    /**
     * Connect to the database
     * 
     * @return void
     * @throws PDOException If connection fails
     */
    protected function connect(): void
    {
        $connection = $this->config['connections'][$this->driver] ?? null;
        
        if (!$connection) {
            throw new PDOException("Database connection configuration not found for driver: {$this->driver}");
        }
        
        $dsn = $this->createDsn($connection);
        $username = $connection['username'] ?? null;
        $password = $connection['password'] ?? null;
        $options = $connection['options'] ?? [];
        
        // Set default PDO options if not provided
        if (!isset($options[PDO::ATTR_ERRMODE])) {
            $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }
        
        if (!isset($options[PDO::ATTR_DEFAULT_FETCH_MODE])) {
            $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        }
        
        if (!isset($options[PDO::ATTR_EMULATE_PREPARES]) && $this->driver !== 'oracle' && $this->driver !== 'oci') {
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
        }
        
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
            
            // Execute specific initialization queries for different database engines
            $this->initializeConnection();
            
        } catch (PDOException $e) {
            throw new PDOException("Failed to connect to database ({$this->driver}): " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Run database-specific initialization queries after connection
     * 
     * @return void
     */
    protected function initializeConnection(): void
    {
        switch ($this->driver) {
            case 'mysql':
            case 'mariadb':
                // Set strict mode if configured
                if ($this->config['connections'][$this->driver]['strict'] ?? false) {
                    $this->pdo->exec("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
                }
                break;
                
            case 'pgsql':
                // Set schema search path if provided
                $schema = $this->config['connections'][$this->driver]['search_path'] ?? 'public';
                if ($schema) {
                    $this->pdo->exec("SET search_path TO {$schema}");
                }
                break;
                
            case 'oracle':
            case 'oci':
                // Set session parameters for Oracle
                $this->pdo->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
                $this->pdo->exec("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF'");
                break;
        }
    }
    
    /**
     * Create the DSN string based on the connection configuration
     * 
     * @param array $connection The connection configuration
     * @return string The DSN string
     */
    protected function createDsn(array $connection): string
    {
        $driver = $this->driver;
        
        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                $charset = $connection['charset'] ?? 'utf8mb4';
                $socket = $connection['unix_socket'] ?? '';
                
                if (!empty($socket) && file_exists($socket)) {
                    return "mysql:unix_socket={$socket};dbname={$connection['database']};charset={$charset}";
                }
                
                return "mysql:host={$connection['host']};port={$connection['port']};dbname={$connection['database']};charset={$charset}";
            
            case 'pgsql':
                $dsn = "pgsql:host={$connection['host']};port={$connection['port']};dbname={$connection['database']}";
                
                // Add optional parameters
                if (isset($connection['sslmode'])) {
                    $dsn .= ";sslmode={$connection['sslmode']}";
                }
                
                return $dsn;
                
            case 'sqlite':
                $path = $connection['database'];
                
                // Handle in-memory database
                if ($path === ':memory:') {
                    return "sqlite::memory:";
                }
                
                // Handle file path
                if (strpos($path, '/') !== 0) {
                    // Relative path - resolve against base path
                    $path = base_path($path);
                }
                
                // Ensure directory exists
                $directory = dirname($path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                return "sqlite:{$path}";
                
            case 'sqlsrv':
                $dsn = "sqlsrv:Server={$connection['host']}";
                
                // Add port if specified
                if (!empty($connection['port'])) {
                    $dsn .= ",{$connection['port']}";
                }
                
                // Add database name
                $dsn .= ";Database={$connection['database']}";
                
                // Add optional parameters
                if (!empty($connection['charset'])) {
                    $dsn .= ";CharacterSet={$connection['charset']}";
                }
                
                if (isset($connection['encrypt'])) {
                    $dsn .= ";Encrypt=" . ($connection['encrypt'] === 'yes' ? 'true' : 'false');
                }
                
                if (isset($connection['trust_server_certificate'])) {
                    $dsn .= ";TrustServerCertificate=" . ($connection['trust_server_certificate'] ? 'true' : 'false');
                }
                
                return $dsn;
            
            case 'oracle':
            case 'oci':
                // Handle different Oracle connection formats
                if (!empty($connection['service_name'])) {
                    // Connect using service name
                    return "oci:dbname=//{$connection['host']}:{$connection['port']}/{$connection['service_name']}";
                } elseif (!empty($connection['tns'])) {
                    // Connect using TNS
                    return "oci:dbname={$connection['tns']}";
                } else {
                    // Connect using SID
                    return "oci:dbname={$connection['host']}:{$connection['port']}/{$connection['database']}";
                }
                
            default:
                throw new PDOException("Unsupported database driver: {$driver}");
        }
    }
    
    /**
     * Get the PDO instance
     * 
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Get the database driver
     * 
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }
    
    /**
     * Execute a raw SQL query
     * 
     * @param string $query The SQL query
     * @param array $params Query parameters
     * @return bool Success indicator
     */
    public function execute(string $query, array $params = []): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }
    
    /**
     * Run a select query and fetch all results
     * 
     * @param string $query The SQL query
     * @param array $params Query parameters
     * @return array Query results
     */
    public function select(string $query, array $params = []): array
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchAll();
    }
    
    /**
     * Run an insert query
     * 
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int|false Last insert ID or false on failure
     */
    public function insert(string $table, array $data)
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $statement = $this->pdo->prepare($query);
        if ($statement->execute(array_values($data))) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool Success indicator
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool Success indicator
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool Success indicator
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Check if a specific feature is supported by the current database driver
     * 
     * @param string $feature Feature name to check
     * @return bool True if supported, false otherwise
     */
    public function supportsFeature(string $feature): bool
    {
        switch ($feature) {
            case 'json':
                // Check JSON support
                return in_array($this->driver, ['mysql', 'mariadb', 'pgsql']);
                
            case 'returning':
                // Check RETURNING clause support
                return in_array($this->driver, ['pgsql', 'oracle', 'oci']);
                
            case 'upsert':
                // Check UPSERT support
                return in_array($this->driver, ['mysql', 'mariadb', 'pgsql', 'sqlite']);
                
            case 'fulltext':
                // Check full text search support
                return in_array($this->driver, ['mysql', 'mariadb', 'pgsql']);
                
            default:
                return false;
        }
    }
} 