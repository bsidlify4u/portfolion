<?php
namespace Portfolion\Database;

use PDO;

/**
 * Base migration class that all migrations should extend
 */
abstract class Migration {
    protected PDO $connection;
    protected $schema;
    
    public function __construct() {
        // Load database configuration
        $config = require base_path('config/database.php');
        
        $connection = $config['default'];
        $options = $config['connections'][$connection];
        
        // Establish database connection based on the configured driver
        if ($connection === 'sqlite') {
            $path = $options['database'];
            $fullPath = base_path($path);
            
            if (!file_exists($fullPath)) {
                $dir = dirname($fullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($fullPath);
            }
            
            $this->connection = new PDO("sqlite:{$fullPath}");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } elseif ($connection === 'mysql') {
            $host = $options['host'];
            $port = $options['port'];
            $database = $options['database'];
            $username = $options['username'];
            $password = $options['password'];
            
            $this->connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$database}",
                $username,
                $password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        // Initialize schema builder
        $this->schema = new SchemaBuilder($this->connection);
    }
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    abstract public function down(): void;
    
    /**
     * Create a database schema based on the current driver
     * 
     * @param string $table The table name
     * @param callable $callback A callback function that defines the schema
     * @return void
     */
    protected function schema(string $table, callable $callback): void
    {
        $schema = new Schema($table);
        $callback($schema);
        $schema->build();
    }
    
    /**
     * Get a new connection instance
     * 
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return new Connection();
    }
}

/**
 * Schema builder class for defining database tables
 */
class Schema
{
    /**
     * The table name
     * 
     * @var string
     */
    protected string $table;
    
    /**
     * The columns to create
     * 
     * @var array
     */
    protected array $columns = [];
    
    /**
     * The connection instance
     * 
     * @var Connection
     */
    protected Connection $connection;
    
    /**
     * Create a new schema instance
     * 
     * @param string $table The table name
     */
    public function __construct(string $table)
    {
        $this->table = $table;
        $this->connection = new Connection();
    }
    
    /**
     * Add an integer column
     * 
     * @param string $name The column name
     * @param bool $autoIncrement Whether the column should auto-increment
     * @return Column
     */
    public function integer(string $name, bool $autoIncrement = false): Column
    {
        $column = new Column($name, 'integer');
        if ($autoIncrement) {
            $column->autoIncrement();
        }
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add a string column
     * 
     * @param string $name The column name
     * @param int $length The column length
     * @return Column
     */
    public function string(string $name, int $length = 255): Column
    {
        $column = new Column($name, 'string', $length);
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add a text column
     * 
     * @param string $name The column name
     * @return Column
     */
    public function text(string $name): Column
    {
        $column = new Column($name, 'text');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add a boolean column
     * 
     * @param string $name The column name
     * @return Column
     */
    public function boolean(string $name): Column
    {
        $column = new Column($name, 'boolean');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add a date column
     * 
     * @param string $name The column name
     * @return Column
     */
    public function date(string $name): Column
    {
        $column = new Column($name, 'date');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add a datetime column
     * 
     * @param string $name The column name
     * @return Column
     */
    public function datetime(string $name): Column
    {
        $column = new Column($name, 'datetime');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add a timestamp column
     * 
     * @param string $name The column name
     * @return Column
     */
    public function timestamp(string $name): Column
    {
        $column = new Column($name, 'timestamp');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add timestamps (created_at, updated_at)
     * 
     * @return void
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }
    
    /**
     * Build the schema
     * 
     * @return void
     */
    public function build(): void
    {
        $driver = $this->connection->getDriver();
        $pdo = $this->connection->getPdo();
        
        // Build the SQL based on the database driver
        $sql = $this->buildSql($driver);
        
        try {
            $pdo->exec($sql);
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
     * Build the SQL for the schema
     * 
     * @param string $driver The database driver
     * @return string The SQL
     */
    protected function buildSql(string $driver): string
    {
        $columnDefinitions = [];
        
        foreach ($this->columns as $column) {
            $columnDefinitions[] = $column->toSql($driver);
        }
        
        switch ($driver) {
            case 'sqlite':
                return "CREATE TABLE IF NOT EXISTS {$this->table} (" . implode(', ', $columnDefinitions) . ")";
                
            case 'mysql':
            case 'mariadb':
                return "CREATE TABLE IF NOT EXISTS {$this->table} (" . implode(', ', $columnDefinitions) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
            case 'pgsql':
                return "CREATE TABLE IF NOT EXISTS {$this->table} (" . implode(', ', $columnDefinitions) . ")";
                
            case 'sqlsrv':
                return "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='{$this->table}' AND xtype='U') CREATE TABLE {$this->table} (" . implode(', ', $columnDefinitions) . ")";
                
            case 'oci':
                // Oracle doesn't support IF NOT EXISTS, so we need to use a PL/SQL block
                return "
                    BEGIN
                        EXECUTE IMMEDIATE 'CREATE TABLE {$this->table} (" . implode(', ', $columnDefinitions) . ")';
                    EXCEPTION
                        WHEN OTHERS THEN
                            IF SQLCODE = -955 THEN
                                NULL; -- Table already exists
                            ELSE
                                RAISE;
                            END IF;
                    END;
                ";
                
            case 'ibm':
            case 'db2':
                // DB2 doesn't support IF NOT EXISTS directly
                return "CREATE TABLE {$this->table} (" . implode(', ', $columnDefinitions) . ")";
                
            default:
                return "CREATE TABLE IF NOT EXISTS {$this->table} (" . implode(', ', $columnDefinitions) . ")";
        }
    }
}

/**
 * Column definition class
 */
class Column
{
    /**
     * The column name
     * 
     * @var string
     */
    protected string $name;
    
    /**
     * The column type
     * 
     * @var string
     */
    protected string $type;
    
    /**
     * The column length
     * 
     * @var int|null
     */
    protected ?int $length;
    
    /**
     * Whether the column is nullable
     * 
     * @var bool
     */
    protected bool $nullable = false;
    
    /**
     * Whether the column is a primary key
     * 
     * @var bool
     */
    protected bool $primaryKey = false;
    
    /**
     * Whether the column is unique
     * 
     * @var bool
     */
    protected bool $unique = false;
    
    /**
     * Whether the column should auto-increment
     * 
     * @var bool
     */
    protected bool $autoIncrement = false;
    
    /**
     * The column default value
     * 
     * @var mixed
     */
    protected $default = null;
    
    /**
     * Create a new column instance
     * 
     * @param string $name The column name
     * @param string $type The column type
     * @param int|null $length The column length
     */
    public function __construct(string $name, string $type, ?int $length = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
    }
    
    /**
     * Make the column nullable
     * 
     * @return self
     */
    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }
    
    /**
     * Make the column a primary key
     * 
     * @return self
     */
    public function primaryKey(): self
    {
        $this->primaryKey = true;
        return $this;
    }
    
    /**
     * Make the column unique
     * 
     * @return self
     */
    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }
    
    /**
     * Make the column auto-increment
     * 
     * @return self
     */
    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }
    
    /**
     * Set the column default value
     * 
     * @param mixed $value The default value
     * @return self
     */
    public function default($value): self
    {
        $this->default = $value;
        return $this;
    }
    
    /**
     * Convert the column to SQL
     * 
     * @param string $driver The database driver
     * @return string The SQL
     */
    public function toSql(string $driver): string
    {
        $sql = $this->name . ' ' . $this->getTypeDefinition($driver);
        
        if ($this->primaryKey) {
            if ($this->autoIncrement) {
                switch ($driver) {
                    case 'sqlite':
                        $sql .= ' PRIMARY KEY AUTOINCREMENT';
                        break;
                        
                    case 'mysql':
                    case 'mariadb':
                        $sql .= ' PRIMARY KEY AUTO_INCREMENT';
                        break;
                        
                    case 'pgsql':
                        // For PostgreSQL, we use SERIAL type which includes auto-increment
                        break;
                        
                    case 'sqlsrv':
                        $sql .= ' IDENTITY(1,1) PRIMARY KEY';
                        break;
                        
                    case 'oci':
                        $sql .= ' PRIMARY KEY';
                        break;
                        
                    case 'ibm':
                    case 'db2':
                        $sql .= ' GENERATED ALWAYS AS IDENTITY PRIMARY KEY';
                        break;
                        
                    default:
                        $sql .= ' PRIMARY KEY AUTO_INCREMENT';
                        break;
                }
            } else {
                $sql .= ' PRIMARY KEY';
            }
        }
        
        if ($this->unique && !$this->primaryKey) {
            $sql .= ' UNIQUE';
        }
        
        if ($this->nullable) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }
        
        if ($this->default !== null) {
            $sql .= ' DEFAULT ' . $this->getDefaultValue($driver);
        }
        
        return $sql;
    }
    
    /**
     * Get the type definition for the column
     * 
     * @param string $driver The database driver
     * @return string The type definition
     */
    protected function getTypeDefinition(string $driver): string
    {
        switch ($this->type) {
            case 'integer':
                switch ($driver) {
                    case 'sqlite':
                        return 'INTEGER';
                        
                    case 'mysql':
                    case 'mariadb':
                        return $this->autoIncrement ? 'INT UNSIGNED' : 'INT';
                        
                    case 'pgsql':
                        return $this->autoIncrement ? 'SERIAL' : 'INTEGER';
                        
                    case 'sqlsrv':
                        return 'INT';
                        
                    case 'oci':
                        return 'NUMBER(10)';
                        
                    case 'ibm':
                    case 'db2':
                        return 'INTEGER';
                        
                    default:
                        return 'INT';
                }
                
            case 'string':
                $length = $this->length ?? 255;
                
                switch ($driver) {
                    case 'sqlsrv':
                        return "NVARCHAR({$length})";
                        
                    case 'oci':
                        return "VARCHAR2({$length})";
                        
                    default:
                        return "VARCHAR({$length})";
                }
                
            case 'text':
                switch ($driver) {
                    case 'mysql':
                    case 'mariadb':
                        return 'TEXT';
                        
                    case 'pgsql':
                        return 'TEXT';
                        
                    case 'sqlsrv':
                        return 'NVARCHAR(MAX)';
                        
                    case 'oci':
                        return 'CLOB';
                        
                    case 'ibm':
                    case 'db2':
                        return 'CLOB';
                        
                    default:
                        return 'TEXT';
                }
                
            case 'boolean':
                switch ($driver) {
                    case 'mysql':
                    case 'mariadb':
                        return 'TINYINT(1)';
                        
                    case 'pgsql':
                        return 'BOOLEAN';
                        
                    case 'sqlsrv':
                        return 'BIT';
                        
                    case 'oci':
                        return 'NUMBER(1)';
                        
                    case 'ibm':
                    case 'db2':
                        return 'SMALLINT';
                        
                    default:
                        return 'BOOLEAN';
                }
                
            case 'date':
                return 'DATE';
                
            case 'datetime':
                switch ($driver) {
                    case 'mysql':
                    case 'mariadb':
                        return 'DATETIME';
                        
                    case 'sqlsrv':
                        return 'DATETIME2';
                        
                    default:
                        return 'TIMESTAMP';
                }
                
            case 'timestamp':
                switch ($driver) {
                    case 'mysql':
                    case 'mariadb':
                        return 'TIMESTAMP';
                        
                    case 'sqlsrv':
                        return 'DATETIME2';
                        
                    default:
                        return 'TIMESTAMP';
                }
                
            default:
                return 'VARCHAR(255)';
        }
    }
    
    /**
     * Get the default value for the column
     * 
     * @param string $driver The database driver
     * @return string The default value
     */
    protected function getDefaultValue(string $driver): string
    {
        if (is_string($this->default)) {
            return "'" . addslashes($this->default) . "'";
        }
        
        if (is_bool($this->default)) {
            switch ($driver) {
                case 'mysql':
                case 'mariadb':
                    return $this->default ? '1' : '0';
                    
                case 'pgsql':
                    return $this->default ? 'TRUE' : 'FALSE';
                    
                default:
                    return $this->default ? '1' : '0';
            }
        }
        
        if (is_null($this->default)) {
            return 'NULL';
        }
        
        return (string) $this->default;
    }
}
