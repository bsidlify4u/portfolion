<?php
namespace Portfolion\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class SchemaBuilder {
    /** @var array<string, string> */
    protected array $columns = [];
    /** @var array<string> */
    protected array $indexes = [];
    /** @var array<string, string> */
    protected array $foreignKeys = [];
    protected string $table;
    protected PDO $connection;
    protected string $driver;
    
    public function __construct(PDO $connection, string $driver = 'mysql') {
        $this->connection = $connection;
        $this->driver = $driver;
    }
    
    /**
     * Add a column to the schema.
     *
     * @param string $column
     * @param string $type
     * @param array<string, mixed> $options
     * @return $this
     */
    public function addColumn(string $column, string $type, array $options = []): self {
        // Double backtick issue - when the type contains backticks
        if (strpos($type, '`') !== false) {
            $type = str_replace('`', '', $type);
        }

        $sql = $type;
        
        if (!($options['nullable'] ?? false)) {
            $sql .= ' NOT NULL';
        }
        
        if (isset($options['default'])) {
            $sql .= ' DEFAULT ' . $this->getDefaultValue($options['default']);
        }
        
        if ($options['autoIncrement'] ?? false) {
            $sql .= ' AUTO_INCREMENT';
        }
        
        $this->columns[$column] = $sql;
        
        if ($options['index'] ?? false) {
            $this->index($column);
        }
        
        return $this;
    }
    
    /**
     * Make the last column nullable.
     *
     * @return $this
     */
    public function nullable(): self {
        $column = array_key_last($this->columns);
        if ($column === null) {
            throw new RuntimeException('No column defined yet');
        }
        $this->columns[$column] = str_replace(' NOT NULL', ' NULL', $this->columns[$column]);
        return $this;
    }
    
    /**
     * Add a unique index to a column.
     *
     * @param string|null $column
     * @return $this
     */
    public function unique(?string $column = null): self {
        $column = $column ?? array_key_last($this->columns);
        if ($column === null) {
            throw new RuntimeException('No column defined yet');
        }
        $this->indexes[] = "UNIQUE KEY `{$column}_unique` (`$column`)";
        return $this;
    }
    
    /**
     * Add an index to a column.
     *
     * @param string|null $column
     * @return $this
     */
    public function index(?string $column = null): self {
        $column = $column ?? array_key_last($this->columns);
        if ($column === null) {
            throw new RuntimeException('No column defined yet');
        }
        $this->indexes[] = "KEY `{$column}_index` (`$column`)";
        return $this;
    }
    
    /**
     * Set a column as primary key.
     *
     * @param string|null $column
     * @return $this
     */
    public function primary(?string $column = null): self {
        $column = $column ?? array_key_last($this->columns);
        if ($column === null) {
            throw new RuntimeException('No column defined yet');
        }
        $this->indexes[] = "PRIMARY KEY (`$column`)";
        return $this;
    }
    
    /**
     * Convert a PHP value to an SQL default value string.
     *
     * @param mixed $value
     * @return string
     */
    protected function getDefaultValue(mixed $value): string {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        return "'" . addslashes($value) . "'";
    }
    
    public function create(string $table, callable $callback): void {
        $this->table = $table;
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        
        // Create a Blueprint instance to pass to the callback
        $blueprint = new \Portfolion\Database\Schema\Blueprint($this);
        $callback($blueprint);
        
        $this->buildTable();
    }
    
    public function table(string $table, callable $callback): void {
        $this->table = $table;
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        
        $callback($this);
        
        $this->alterTable();
    }
    
    public function drop(string $table): void {
        $sql = "DROP TABLE IF EXISTS {$table}";
        $this->connection->exec($sql);
    }
    
    public function id(string $column = 'id'): self {
        switch ($this->driver) {
            case 'sqlite':
                $this->columns[$column] = "INTEGER PRIMARY KEY AUTOINCREMENT";
                break;
            
            case 'pgsql':
                // PostgreSQL uses SERIAL or BIGSERIAL for auto-incrementing
                $this->columns[$column] = "BIGSERIAL";
                $this->primary($column);
                break;
            
            case 'sqlsrv':
                // SQL Server uses IDENTITY
                $this->columns[$column] = "BIGINT IDENTITY(1,1)";
                $this->primary($column);
                break;
            
            case 'oracle':
            case 'oci':
                // Oracle uses sequences (handled in createOracleSequences)
                $this->columns[$column] = "NUMBER(19) NOT NULL";
                $this->primary($column);
                break;
            
            case 'mysql':
            case 'mariadb':
            default:
                $this->bigInteger($column, true);
                $this->primary($column);
                break;
        }
        
        return $this;
    }
    
    public function string(string $column, int $length = 255): self {
        $this->columns[$column] = "VARCHAR($length)";
        return $this;
    }
    
    public function text(string $column): self {
        switch ($this->driver) {
            case 'sqlsrv':
                // SQL Server uses VARCHAR(MAX) or NVARCHAR(MAX)
                $this->columns[$column] = "NVARCHAR(MAX)";
                break;
            
            case 'oracle':
            case 'oci':
                // Oracle uses CLOB for large text
                $this->columns[$column] = "CLOB";
                break;
            
            default:
                // Most databases have TEXT type
                $this->columns[$column] = "TEXT";
                break;
        }
        
        return $this;
    }
    
    public function integer(string $column, bool $autoIncrement = false): self {
        switch ($this->driver) {
            case 'sqlite':
                $this->columns[$column] = $autoIncrement ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INTEGER";
                break;
            
            case 'pgsql':
                $this->columns[$column] = $autoIncrement ? "SERIAL" : "INTEGER";
                break;
            
            case 'sqlsrv':
                $this->columns[$column] = $autoIncrement ? "INT IDENTITY(1,1)" : "INT";
                break;
            
            case 'oracle':
            case 'oci':
                $this->columns[$column] = "NUMBER(10)" . ($autoIncrement ? " NOT NULL" : "");
                break;
            
            case 'mysql':
            case 'mariadb':
            default:
                $this->columns[$column] = $autoIncrement ? "INTEGER AUTO_INCREMENT" : "INTEGER";
                break;
        }
        
        return $this;
    }
    
    public function bigInteger(string $column, bool $autoIncrement = false): self {
        switch ($this->driver) {
            case 'sqlite':
                $this->columns[$column] = $autoIncrement ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INTEGER";
                break;
            
            case 'pgsql':
                $this->columns[$column] = $autoIncrement ? "BIGSERIAL" : "BIGINT";
                break;
            
            case 'sqlsrv':
                $this->columns[$column] = $autoIncrement ? "BIGINT IDENTITY(1,1)" : "BIGINT";
                break;
            
            case 'oracle':
            case 'oci':
                $this->columns[$column] = "NUMBER(19)" . ($autoIncrement ? " NOT NULL" : "");
                break;
            
            case 'mysql':
            case 'mariadb':
            default:
                $this->columns[$column] = $autoIncrement ? "BIGINT AUTO_INCREMENT" : "BIGINT";
                break;
        }
        
        return $this;
    }
    
    public function boolean(string $column): self {
        $this->columns[$column] = "TINYINT(1)";
        return $this;
    }
    
    public function date(string $column): self {
        $this->columns[$column] = "DATE";
        return $this;
    }
    
    public function dateTime(string $column): self {
        $this->columns[$column] = "DATETIME";
        return $this;
    }
    
    public function timestamp(string $column): self {
        $this->columns[$column] = "TIMESTAMP";
        return $this;
    }
    
    public function decimal(string $column, int $precision = 8, int $scale = 2): self {
        $this->columns[$column] = "DECIMAL($precision, $scale)";
        return $this;
    }
    
    public function float(string $column): self {
        $this->columns[$column] = "FLOAT";
        return $this;
    }
    
    public function json(string $column): self {
        switch ($this->driver) {
            case 'pgsql':
                // PostgreSQL has native JSONB type
                $this->columns[$column] = "JSONB";
                break;
            
            case 'oracle':
            case 'oci':
                // Oracle uses CLOB for JSON
                $this->columns[$column] = "CLOB";
                break;
            
            case 'sqlsrv':
                // SQL Server 2016+ has native JSON support with NVARCHAR(MAX)
                $this->columns[$column] = "NVARCHAR(MAX)";
                break;
            
            case 'sqlite':
                // SQLite stores JSON as TEXT
                $this->columns[$column] = "TEXT";
                break;
            
            case 'mysql':
            case 'mariadb':
            default:
                // MySQL 5.7+ has native JSON type
                $this->columns[$column] = "JSON";
                break;
        }
        
        return $this;
    }
    
    public function timestamps(): self {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
        return $this;
    }
    
    public function softDeletes(): self {
        return $this->timestamp('deleted_at')->nullable();
    }
    
    /**
     * Set a default value for the last column.
     *
     * @param mixed $value
     * @return $this
     */
    public function default(mixed $value): self {
        $column = array_key_last($this->columns);
        if ($column === null) {
            throw new RuntimeException('No column defined yet');
        }
        
        // Add DEFAULT clause to column definition
        if (strpos($this->columns[$column], ' DEFAULT ') === false) {
            $this->columns[$column] .= ' DEFAULT ' . $this->getDefaultValue($value);
        } else {
            // Replace existing default
            $this->columns[$column] = preg_replace(
                '/DEFAULT\s+[^,)]+/i', 
                'DEFAULT ' . $this->getDefaultValue($value), 
                $this->columns[$column]
            );
        }
        
        return $this;
    }
    
    /**
     * Add a nullable column to the table.
     *
     * @param string|null $column
     * @param string $type
     * @return static
     */
    public function nullableColumn(?string $column, string $type): static {
        if ($column === null) {
            throw new InvalidArgumentException('Column name cannot be null');
        }
        $this->addColumn($column, $type, ['nullable' => true]);
        return $this;
    }
    
    /**
     * Add a unique column to the table.
     *
     * @param string|null $column
     * @param string $type
     * @return static
     */
    public function uniqueColumn(?string $column, string $type): static {
        if ($column === null) {
            throw new InvalidArgumentException('Column name cannot be null');
        }
        $this->addColumn($column, $type, ['unique' => true]);
        return $this;
    }
    
    /**
     * Add an indexed column to the table.
     *
     * @param string|null $column
     * @param string $type
     * @return static
     */
    public function indexedColumn(?string $column, string $type): static {
        if ($column === null) {
            throw new InvalidArgumentException('Column name cannot be null');
        }
        $this->addColumn($column, $type, ['index' => true]);
        return $this;
    }
    
    /**
     * Add an index for specified columns.
     *
     * @param string $name The name of the index
     * @param array $columns The columns to index
     * @return $this
     */
    public function addIndex(string $name, array $columns): self {
        $columnList = implode(', ', $columns);
        $this->indexes[] = "KEY `{$name}` ({$columnList})";
        return $this;
    }
    
    /**
     * Get the current table name.
     *
     * @return string
     */
    public function getTable(): string {
        return $this->table;
    }
    
    protected function buildTable(): void {
        if (empty($this->table)) {
            throw new RuntimeException('No table name specified');
        }
        
        if (empty($this->columns)) {
            throw new RuntimeException('No columns specified for table');
        }
        
        // Begin constructing the CREATE TABLE statement
        $columnsSQL = [];
        foreach ($this->columns as $column => $definition) {
            $columnsSQL[] = "`{$column}` {$definition}";
        }
        
        // Convert indexes array to SQL
        $indexesSQL = $this->indexes;
        
        // Convert foreign keys array to SQL
        $foreignKeysSQL = [];
        foreach ($this->foreignKeys as $column => $definition) {
            $foreignKeysSQL[] = $definition;
        }
        
        // Combine all elements into the final SQL
        $elements = array_merge($columnsSQL, $indexesSQL, $foreignKeysSQL);
        
        // Build the SQL statement with the correct syntax for each driver
        switch ($this->driver) {
            case 'sqlite':
                $sql = "CREATE TABLE `{$this->table}` (\n    " . implode(",\n    ", $elements) . "\n)";
                break;
            
            case 'pgsql':
                // PostgreSQL uses double quotes for identifiers
                $sql = str_replace('`', '"', "CREATE TABLE \"{$this->table}\" (\n    " . implode(",\n    ", $elements) . "\n)");
                break;
            
            case 'sqlsrv':
                // SQL Server doesn't use backticks for quoting
                $sql = str_replace('`', '[', "CREATE TABLE [{$this->table}] (\n    " . implode(",\n    ", $elements) . "\n)");
                $sql = str_replace('AUTO_INCREMENT', 'IDENTITY(1,1)', $sql);
                break;
            
            case 'oracle':
            case 'oci':
                // Oracle uses double quotes for identifiers
                $sql = str_replace('`', '"', "CREATE TABLE \"{$this->table}\" (\n    " . implode(",\n    ", $elements) . "\n)");
                $sql = str_replace('AUTO_INCREMENT', '', $sql); // Oracle uses sequences
                break;
            
            case 'mysql':
            case 'mariadb':
            default:
                $sql = "CREATE TABLE `{$this->table}` (\n    " . implode(",\n    ", $elements) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                break;
        }
        
        try {
            // Execute the SQL statement
            $this->connection->exec($sql);
            
            // For Oracle, create sequences and triggers for auto-increment columns
            if (in_array($this->driver, ['oracle', 'oci'])) {
                $this->createOracleSequences();
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to create table '{$this->table}': " . $e->getMessage());
        }
    }
    
    protected function alterTable(): void {
        foreach ($this->columns as $name => $type) {
            $sql = sprintf(
                "ALTER TABLE `%s` ADD COLUMN IF NOT EXISTS `%s` %s",
                $this->table,
                $name,
                $type
            );
            $this->connection->exec($sql);
        }
        
        foreach ($this->indexes as $index) {
            try {
                $sql = sprintf(
                    "ALTER TABLE `%s` ADD %s",
                    $this->table,
                    $index
                );
                $this->connection->exec($sql);
            } catch (PDOException $e) {
                // Index might already exist
                continue;
            }
        }
    }
    
    /**
     * Make the column use CURRENT_TIMESTAMP as default value.
     *
     * @return $this
     */
    public function useCurrent(): self {
        $column = array_key_last($this->columns);
        if ($column === null) {
            throw new RuntimeException('No column defined yet');
        }
        
        // Add DEFAULT CURRENT_TIMESTAMP to column definition
        if (strpos($this->columns[$column], 'DEFAULT') === false) {
            $this->columns[$column] .= ' DEFAULT CURRENT_TIMESTAMP';
        } else {
            $this->columns[$column] = preg_replace('/DEFAULT\s+[^,\s]+/', 'DEFAULT CURRENT_TIMESTAMP', $this->columns[$column]);
        }
        
        return $this;
    }
    
    /**
     * Create sequences and triggers for Oracle auto-increment columns
     * 
     * @return void
     */
    protected function createOracleSequences(): void {
        // Find columns that were marked for auto-increment
        foreach ($this->columns as $column => $definition) {
            if (strpos($definition, 'AUTO_INCREMENT') !== false) {
                $sequenceName = "{$this->table}_{$column}_seq";
                $triggerName = "{$this->table}_{$column}_trg";
                
                // Create sequence
                $this->connection->exec("CREATE SEQUENCE {$sequenceName} START WITH 1 INCREMENT BY 1 NOCACHE");
                
                // Create trigger
                $trigger = "
                CREATE OR REPLACE TRIGGER {$triggerName}
                BEFORE INSERT ON \"{$this->table}\"
                FOR EACH ROW
                BEGIN
                    IF :new.\"{$column}\" IS NULL THEN
                        SELECT {$sequenceName}.NEXTVAL INTO :new.\"{$column}\" FROM dual;
                    END IF;
                END;";
                
                $this->connection->exec($trigger);
            }
        }
    }
}
