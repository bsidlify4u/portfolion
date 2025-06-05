<?php
namespace Portfolion\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Portfolion\Config;

/**
 * SQL query builder with type-safe methods.
 */
class QueryBuilder {
    protected PDO $connection;
    protected string $table = '';
    protected array $columns = ['*'];
    /** @var array<array{type: string, column: string, operator: string, value: mixed, boolean: string}> */
    protected array $wheres = [];
    /** @var array<array{type: string, table: string, first: string, operator: string, second: string}> */
    protected array $joins = [];
    /** @var array<array{column: string, direction: string}> */
    protected array $orders = [];
    /** @var array<string> */
    protected array $groups = [];
    /** @var array<array{type: string, column: string, operator: string, value: mixed}> */
    protected array $having = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    /** @var array<string, array<int|string, mixed>> */
    protected array $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
        'union' => []
    ];
    
    /** @var array<string,mixed> Cache of connection parameters */
    private array $connectionParams = [];
    
    /**
     * @throws RuntimeException When database connection fails
     */
    public function __construct() {
        $config = Config::getInstance();
        
        // Get database configuration
        $connection = $config->get('database.default', 'mysql');
        $dbConfig = $config->get("database.connections.{$connection}");
        
        if (!$dbConfig) {
            throw new RuntimeException("Database configuration not found for connection: {$connection}");
        }
        
        $host = $dbConfig['host'] ?? 'localhost';
        $port = $dbConfig['port'] ?? '3306';
        $dbname = $dbConfig['database'] ?? 'portfolion';
        $username = $dbConfig['username'] ?? 'portfolion';
        $password = $dbConfig['password'] ?? 'portfolion';
        
        $errors = [];
        
        // Try socket connection first
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $socketPaths = [
                '/var/run/mysqld/mysqld.sock',
                '/tmp/mysql.sock',
                '/var/lib/mysql/mysql.sock'
            ];
            
            foreach ($socketPaths as $socket) {
                if (file_exists($socket)) {
                    try {
                        $this->connection = new PDO(
                            "mysql:unix_socket={$socket};dbname={$dbname}",
                            $username,
                            $password,
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_EMULATE_PREPARES => false,
                            ]
                        );
                        // Test the connection
                        $this->connection->query('SELECT 1');
                        return;
                    } catch (PDOException $e) {
                        $errors[] = "Socket connection failed ({$socket}): " . $e->getMessage();
                    }
                }
            }
        }
        
        // Try TCP connection as fallback
        try {
            // Force TCP connection by using IP
            $tcpHost = ($host === 'localhost') ? '127.0.0.1' : $host;
            $this->connection = new PDO(
                "mysql:host={$tcpHost};port={$port};dbname={$dbname}",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            // Test the connection
            $this->connection->query('SELECT 1');
            return;
        } catch (PDOException $e) {
            $errors[] = "TCP connection failed ({$tcpHost}:{$port}): " . $e->getMessage();
        }
        
        // If we get here, all connection attempts failed
        throw new RuntimeException(
            "Database connection failed. Attempted the following:\n" . 
            implode("\n", $errors) . "\n" .
            "Configuration used:\n" .
            "Host: {$host}\n" .
            "Port: {$port}\n" .
            "Database: {$dbname}\n" .
            "Username: {$username}"
        );
    }
    
    /**
     * Get the active PDO connection
     *
     * @return PDO
     * @throws RuntimeException if no connection is available
     */
    public function getConnection(): PDO {
        if (!isset($this->connection)) {
            throw new RuntimeException("No active database connection");
        }
        return $this->connection;
    }

    /**
     * Check if the connection is still alive and reconnect if needed
     *
     * @return bool True if connection is alive or successfully reconnected
     */
    public function ensureConnected(): bool {
        if (!isset($this->connection)) {
            return false;
        }

        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("Connection lost, attempting to reconnect: " . $e->getMessage());
            try {
                $this->__construct(); // Attempt to reconnect
                return true;
            } catch (RuntimeException $e) {
                error_log("Reconnection failed: " . $e->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Set the table for the query.
     * 
     * @param string $table
     * @return $this
     * @throws RuntimeException If table name is empty
     */
    public function table(string $table): self {
        if (empty($table)) {
            throw new RuntimeException("Table name cannot be empty");
        }
        $this->table = $table;
        return $this;
    }
    
    /**
     * Add a basic where clause to the query.
     * 
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    /**
     * Add a where clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value, string $boolean = 'and'): self {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => strtolower($boolean)
        ];
        
        $this->addBinding('where', $value);
        
        return $this;
    }
    
    /**
     * Execute the query and get the first result.
     * 
     * @template T of object
     * @param class-string<T>|null $class
     * @return T|array<string, mixed>|null
     * @throws RuntimeException If the query fails
     */
    public function first(?string $class = null): mixed {
        $this->limit = 1;
        $results = $this->get($class);
        return $results[0] ?? null;
    }
    
    /**
     * Execute the query and get all results.
     * 
     * @template T of object
     * @param class-string<T>|null $class
     * @return array<int, T|array<string, mixed>>
     * @throws RuntimeException If the query fails
     */
    public function get(?string $class = null): array {
        try {
            $statement = $this->prepare($this->toSql());
            $this->bindValues($statement);
            $statement->execute();
            
            if ($class !== null) {
                return $statement->fetchAll(PDO::FETCH_CLASS, $class);
            }
            
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Query execution failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Prepare a query for execution.
     * 
     * @param string $query
     * @return PDOStatement
     * @throws RuntimeException If prepare fails
     */
    protected function prepare(string $query): PDOStatement {
        try {
            return $this->connection->prepare($query);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to prepare query: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Add a binding to the query.
     * 
     * @param string $type
     * @param mixed $value
     * @return void
     */
    protected function addBinding(string $type, mixed $value): void {
        $this->bindings[$type][] = $value;
    }
    
    /**
     * Get the SQL query string.
     * 
     * @return string
     */
    protected function toSql(): string {
        $sql = ['SELECT', implode(', ', $this->columns)];
        $sql[] = 'FROM ' . $this->table;
        
        if (!empty($this->wheres)) {
            $sql[] = $this->compileWheres();
        }
        
        if (!empty($this->groups)) {
            $sql[] = 'GROUP BY ' . implode(', ', $this->groups);
        }
        
        if (!empty($this->having)) {
            $sql[] = $this->compileHaving();
        }
        
        if (!empty($this->orders)) {
            $sql[] = 'ORDER BY ' . implode(', ', array_map(
                fn($order) => $order['column'] . ' ' . $order['direction'],
                $this->orders
            ));
        }
        
        if ($this->limit !== null) {
            $sql[] = 'LIMIT ' . $this->limit;
            
            if ($this->offset !== null) {
                $sql[] = 'OFFSET ' . $this->offset;
            }
        }
        
        return implode(' ', array_filter($sql));
    }
    
    /**
     * Insert a record into the database and get the ID.
     * 
     * @param array<string, mixed> $values
     * @return int|false
     * @throws RuntimeException
     */
    public function insertGetId(array $values): int|false {
        try {
            $columns = array_keys($values);
            $bindings = [];
            
            // Format date values
            foreach ($values as $column => $value) {
                if ($value instanceof \DateTime) {
                    if ($column === 'due_date') {
                        $bindings[] = $value->format('Y-m-d');
                    } else {
                        $bindings[] = $value->format('Y-m-d H:i:s');
                    }
                } elseif ($column === 'due_date' && is_string($value)) {
                    // Special handling for due_date column
                    try {
                        $date = new \DateTime($value);
                        $bindings[] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If parsing fails, use null
                        $bindings[] = null;
                    }
                } else {
                    $bindings[] = $value;
                }
            }
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->table,
                implode(', ', $columns),
                implode(', ', array_fill(0, count($values), '?'))
            );
            
            error_log("SQL: $sql");
            error_log("Bindings: " . print_r($bindings, true));
            
            $statement = $this->prepare($sql);
            $statement->execute($bindings);
            
            return (int)$this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            throw new RuntimeException("Failed to insert record: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Delete records from the database.
     * 
     * @return int|false Number of affected rows
     * @throws RuntimeException
     */
    public function delete(): int|false {
        try {
            $sql = sprintf("DELETE FROM %s %s", 
                $this->table,
                $this->compileWheres()
            );
            
            $statement = $this->prepare($sql);
            $this->bindValues($statement);
            $statement->execute();
            
            return $statement->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to delete records: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Update records in the database.
     * 
     * @param array<string, mixed> $values
     * @return int|false Number of affected rows
     * @throws RuntimeException
     */
    public function update(array $values): int|false {
        try {
            // Debug the input values
            error_log("Update values: " . print_r($values, true));
            
            $sets = [];
            $updateBindings = [];
            
            foreach ($values as $column => $value) {
                $sets[] = sprintf("%s = ?", $column);
                
                // Handle date values
                if ($value instanceof \DateTime) {
                    if ($column === 'due_date') {
                        $updateBindings[] = $value->format('Y-m-d');
                    } else {
                        $updateBindings[] = $value->format('Y-m-d H:i:s');
                    }
                } elseif ($column === 'due_date' && is_string($value)) {
                    // Special handling for due_date column
                    try {
                        $date = new \DateTime($value);
                        $updateBindings[] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If parsing fails, use null
                        $updateBindings[] = null;
                    }
                } else {
                    $updateBindings[] = $value;
                }
            }
            
            // Store update bindings separately
            $this->bindings['update'] = $updateBindings;
            
            $sql = sprintf(
                "UPDATE %s SET %s %s",
                $this->table,
                implode(', ', $sets),
                $this->compileWheres()
            );
            
            error_log("SQL: $sql");
            error_log("Update bindings: " . print_r($updateBindings, true));
            error_log("All bindings: " . print_r($this->bindings, true));
            
            // Prepare the statement
            $statement = $this->prepare($sql);
            
            // Manually bind update values first
            $paramIndex = 1;
            foreach ($updateBindings as $value) {
                $type = $this->getParameterType($value);
                error_log("Binding update param $paramIndex with value: " . (is_null($value) ? 'NULL' : $value) . " of type: $type");
                $statement->bindValue($paramIndex++, $value, $type);
            }
            
            // Then bind where values
            foreach ($this->bindings['where'] as $value) {
                $type = $this->getParameterType($value);
                error_log("Binding where param $paramIndex with value: " . (is_null($value) ? 'NULL' : $value) . " of type: $type");
                $statement->bindValue($paramIndex++, $value, $type);
            }
            
            // Execute the statement
            $result = $statement->execute();
            error_log("Statement executed: " . ($result ? 'true' : 'false'));
            
            return $statement->rowCount();
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            throw new RuntimeException("Failed to update records: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Insert a new record into the database.
     * 
     * @param array<string, mixed> $values
     * @return bool
     * @throws RuntimeException
     */
    public function insert(array $values): bool {
        try {
            $columns = array_keys($values);
            $bindings = [];
            
            // Format date values
            foreach ($values as $column => $value) {
                if ($value instanceof \DateTime) {
                    if ($column === 'due_date') {
                        $bindings[] = $value->format('Y-m-d');
                    } else {
                        $bindings[] = $value->format('Y-m-d H:i:s');
                    }
                } elseif ($column === 'due_date' && is_string($value)) {
                    // Special handling for due_date column
                    try {
                        $date = new \DateTime($value);
                        $bindings[] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If parsing fails, use null
                        $bindings[] = null;
                    }
                } else {
                    $bindings[] = $value;
                }
            }
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->table,
                implode(', ', $columns),
                implode(', ', array_fill(0, count($values), '?'))
            );
            
            error_log("SQL: $sql");
            error_log("Bindings: " . print_r($bindings, true));
            
            $statement = $this->prepare($sql);
            return $statement->execute($bindings);
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            throw new RuntimeException("Failed to insert record: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Check if any records exist that match the current query.
     * 
     * @return bool
     * @throws RuntimeException
     */
    public function exists(): bool {
        $clone = clone $this;
        $clone->columns = ['1'];
        return $clone->first() !== null;
    }
    
    /**
     * Bind values to a prepared statement.
     * 
     * @param PDOStatement $statement
     * @throws PDOException
     */
    protected function bindValues(PDOStatement $statement): void {
        $values = [];
        $columns = [];
        
        // Get column names from bindings
        foreach ($this->bindings as $type => $typeBindings) {
            foreach ($typeBindings as $key => $value) {
                if (is_string($key)) {
                    $columns[$key] = $key;
                }
                $values[] = $value;
            }
        }
        
        $index = 0;
        foreach ($values as $key => $value) {
            // Convert DateTime objects to strings
            if ($value instanceof \DateTime) {
                // Check if this is for the due_date column
                $columnName = is_string($key) ? $key : ($columns[$index] ?? null);
                if ($columnName === 'due_date') {
                    $value = $value->format('Y-m-d');
                } else {
                    $value = $value->format('Y-m-d H:i:s');
                }
            }
            
            // For debugging
            error_log("Binding param " . (is_string($key) ? $key : $key + 1) . " with value: " . (is_null($value) ? 'NULL' : $value) . " of type: " . $this->getParameterType($value));
            
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $this->getParameterType($value)
            );
            
            $index++;
        }
    }
    
    /**
     * Get the PDO parameter type for a value.
     * 
     * @param mixed $value
     * @return int
     */
    protected function getParameterType(mixed $value): int {
        return match(true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            $value instanceof \DateTime => PDO::PARAM_STR,
            default => PDO::PARAM_STR,
        };
    }
    
    /**
     * Compile the where clauses.
     * 
     * @return string
     */
    protected function compileWheres(): string {
        if (empty($this->wheres)) {
            return '';
        }
        
        $wheres = [];
        foreach ($this->wheres as $where) {
            $wheres[] = sprintf(
                "%s %s %s %s",
                $where['boolean'] === 'and' ? 'AND' : 'OR',
                $where['column'],
                $where['operator'],
                '?'
            );
        }
        
        return 'WHERE ' . ltrim(implode(' ', $wheres), 'AND ');
    }
    
    /**
     * Compile the having clauses.
     * 
     * @return string
     */
    protected function compileHaving(): string {
        if (empty($this->having)) {
            return '';
        }
        
        $having = [];
        foreach ($this->having as $h) {
            $having[] = sprintf('%s %s ?', $h['column'], $h['operator']);
        }
        return 'HAVING ' . implode(' AND ', $having);
    }
}
