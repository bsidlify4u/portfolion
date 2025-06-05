<?php

namespace Portfolion\Session\Handlers;

use Portfolion\Config;
use PDO;
use RuntimeException;

/**
 * Database session handler for the Portfolion framework
 * 
 * This class handles sessions stored in a database.
 */
class DatabaseSessionHandler extends AbstractSessionHandler
{
    /**
     * @var PDO Database connection
     */
    protected PDO $connection;
    
    /**
     * @var string Database table name
     */
    protected string $table;
    
    /**
     * Create a new database session handler instance
     * 
     * @param Config $config Configuration instance
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        
        // Get the database configuration
        $connection = $config->get('session.connection');
        $this->table = $config->get('session.table', 'sessions');
        
        // Create the database connection
        $this->connection = $this->createConnection($connection);
        
        // Ensure the sessions table exists
        $this->ensureSessionTableExists();
    }
    
    /**
     * Initialize the session handler
     * 
     * @param string $savePath The path where to store/retrieve the session
     * @param string $sessionName The session name
     * @return bool Whether initialization was successful
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }
    
    /**
     * Read the session data
     * 
     * @param string $id The session ID
     * @return string|false The session data or false on failure
     */
    public function read($id)
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT payload FROM {$this->table} WHERE id = :id AND expires_at > :time"
            );
            
            $stmt->execute([
                ':id' => $id,
                ':time' => time(),
            ]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['payload'];
            }
            
            return '';
        } catch (\Exception $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Write the session data
     * 
     * @param string $id The session ID
     * @param string $data The session data
     * @return bool Whether the operation was successful
     */
    public function write($id, $data): bool
    {
        $lifetime = (int) ini_get('session.gc_maxlifetime');
        $expiresAt = time() + $lifetime;
        
        try {
            // Check if the session already exists
            $stmt = $this->connection->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE id = :id"
            );
            
            $stmt->execute([':id' => $id]);
            $exists = (int) $stmt->fetchColumn() > 0;
            
            if ($exists) {
                // Update existing session
                $stmt = $this->connection->prepare(
                    "UPDATE {$this->table} SET payload = :payload, expires_at = :expires_at, last_activity = :last_activity WHERE id = :id"
                );
            } else {
                // Insert new session
                $stmt = $this->connection->prepare(
                    "INSERT INTO {$this->table} (id, payload, expires_at, last_activity) VALUES (:id, :payload, :expires_at, :last_activity)"
                );
            }
            
            return $stmt->execute([
                ':id' => $id,
                ':payload' => $data,
                ':expires_at' => $expiresAt,
                ':last_activity' => time(),
            ]);
        } catch (\Exception $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy a session
     * 
     * @param string $id The session ID
     * @return bool Whether the operation was successful
     */
    public function destroy($id): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE id = :id"
            );
            
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Garbage collection
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    public function gc($lifetime): bool
    {
        return $this->clean($lifetime);
    }
    
    /**
     * Check if a session exists
     * 
     * @param string $id The session ID
     * @return bool Whether the session exists
     */
    public function exists(string $id): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE id = :id"
            );
            
            $stmt->execute([':id' => $id]);
            
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Session exists check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean expired sessions
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    public function clean(int $lifetime): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE expires_at < :time"
            );
            
            return $stmt->execute([':time' => time()]);
        } catch (\Exception $e) {
            error_log("Session clean error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a database connection
     * 
     * @param string|null $connection The connection name
     * @return PDO The database connection
     * @throws RuntimeException If the connection cannot be created
     */
    protected function createConnection(?string $connection = null): PDO
    {
        // Get the connection configuration
        $connection = $connection ?? $this->config->get('database.default', 'mysql');
        $dbConfig = $this->config->get("database.connections.{$connection}");
        
        if (!$dbConfig) {
            throw new RuntimeException("Database configuration not found for connection: {$connection}");
        }
        
        // Extract connection details
        $driver = $dbConfig['driver'] ?? 'mysql';
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? '3306';
        $database = $dbConfig['database'] ?? 'portfolion';
        $username = $dbConfig['username'] ?? 'portfolion';
        $password = $dbConfig['password'] ?? 'portfolion';
        $charset = $dbConfig['charset'] ?? 'utf8mb4';
        
        // Create DSN
        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
        
        // Create connection
        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to connect to database: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure the sessions table exists
     * 
     * @return void
     * @throws RuntimeException If the table cannot be created
     */
    protected function ensureSessionTableExists(): void
    {
        try {
            // Check if the table exists
            $stmt = $this->connection->prepare(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table"
            );
            
            $stmt->execute([':table' => $this->table]);
            
            if ($stmt->fetchColumn() === false) {
                // Create the table
                $this->connection->exec(
                    "CREATE TABLE {$this->table} (
                        id VARCHAR(128) NOT NULL,
                        payload TEXT NOT NULL,
                        expires_at INT UNSIGNED NOT NULL,
                        last_activity INT UNSIGNED NOT NULL,
                        PRIMARY KEY (id),
                        INDEX (expires_at)
                    )"
                );
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to create sessions table: " . $e->getMessage());
        }
    }
} 