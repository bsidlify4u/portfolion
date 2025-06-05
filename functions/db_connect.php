<?php
/**
 * Database Connection Function
 * Provides a simple and secure way to connect to MySQL database
 */

// Include database configuration
require_once __DIR__ . '/database.php';

/**
 * Sanitize and validate table name to prevent SQL injection
 * 
 * @param string $table Table name
 * @return string Sanitized table name
 * @throws Exception if table name is invalid
 */
function validateTableName($table) {
    // Remove any dangerous characters
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    
    if (empty($table) || $table !== trim($table)) {
        throw new Exception('Invalid table name provided');
    }
    return $table;
}

/**
 * Validate and sanitize column names
 * 
 * @param array $columns Column names
 * @return array Sanitized column names
 * @throws Exception if column names are invalid
 */
function validateColumns($columns) {
    if (!is_array($columns)) {
        throw new Exception('Columns must be provided as an array');
    }
    
    return array_map(function($column) {
        // Handle * specially
        if ($column === '*') return $column;
        
        // Remove any dangerous characters
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        
        if (empty($column) || $column !== trim($column)) {
            throw new Exception('Invalid column name provided');
        }
        return $column;
    }, $columns);
}

/**
 * Validate and sanitize input data
 * 
 * @param array $data Input data
 * @return array Sanitized data
 * @throws Exception if data is invalid
 */
function validateData($data) {
    if (!is_array($data)) {
        throw new Exception('Data must be provided as an array');
    }
    
    $sanitized = [];
    foreach ($data as $key => $value) {
        // Sanitize keys (column names)
        $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
        if (empty($key)) {
            throw new Exception('Invalid column name in data');
        }
        
        // Allow null values but validate other types
        if ($value !== null) {
            // Prevent code injection
            if (is_string($value)) {
                // Remove null bytes and other dangerous characters
                $value = str_replace(["\0", "\r", "\x1a"], '', $value);
            } elseif (!is_numeric($value) && !is_bool($value)) {
                throw new Exception('Invalid data type provided');
            }
        }
        
        $sanitized[$key] = $value;
    }
    
    return $sanitized;
}

/**
 * Rate limiting function to prevent brute force attacks
 * 
 * @param string $operation Operation name (e.g., 'insert', 'update')
 * @param int $maxAttempts Maximum attempts allowed per minute
 * @throws Exception if rate limit is exceeded
 */
function checkRateLimit($operation, $maxAttempts = 60) {
    $cacheFile = sys_get_temp_dir() . '/db_rate_limit_' . $operation . '.json';
    
    // Load existing attempts
    $attempts = [];
    if (file_exists($cacheFile)) {
        $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Clean old attempts (older than 1 minute)
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now) {
        return $timestamp > ($now - 60);
    });
    
    // Add current attempt
    $attempts[] = $now;
    
    // Save attempts
    file_put_contents($cacheFile, json_encode($attempts), LOCK_EX);
    
    // Check if limit is exceeded
    if (count($attempts) > $maxAttempts) {
        throw new Exception('Rate limit exceeded. Please try again later.');
    }
}

/**
 * Create a secure database connection
 * 
 * @return PDO Database connection object
 * @throws Exception if connection fails
 */
function connectDatabase() {
    try {
        // Get enhanced SSL configuration
        $sslConfig = getEnhancedSSLConfig();
        
        // Set default options for secure connection
        $options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $sslConfig);

        // Add SSL configuration if enabled
        if (DB_SSL) {
            $options[PDO::MYSQL_ATTR_SSL_KEY] = DB_SSL_KEY;
            $options[PDO::MYSQL_ATTR_SSL_CERT] = DB_SSL_CERT;
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        }

        // Add timeout configuration
        $options[PDO::ATTR_TIMEOUT] = DB_TIMEOUT;

        // Create DSN
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s",
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        // Create and return connection
        return new PDO($dsn, DB_USER, DB_PASSWORD, $options);

    } catch (PDOException $e) {
        // Log error securely without exposing sensitive information
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception('Database connection failed. Please contact administrator.');
    }
}

/**
 * Execute a prepared statement with parameters
 * 
 * @param PDO $connection Database connection
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return PDOStatement
 * @throws Exception if query fails
 */
function executeQuery($connection, $query, $params = []) {
    try {
        $stmt = $connection->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception('Database query failed. Please try again later.');
    }
}

/**
 * Dynamically retrieve data from any table with flexible conditions
 * 
 * @param PDO $connection Database connection
 * @param string $table Table name
 * @param array $conditions Associative array of conditions (field => value)
 * @param array $columns Specific columns to retrieve (default: all columns)
 * @param string $orderBy Order by clause (e.g., "id DESC")
 * @param int $limit Number of rows to return (0 for all)
 * @param int $offset Starting point for result set
 * @return array Results as associative array
 * @throws Exception if query fails
 */
/**
 * @internal This is an internal helper function, use the public functions instead
 */
function getDataFromTable($connection, $table, $conditions = [], $columns = ['*'], $orderBy = '', $limit = 0, $offset = 0) {
    try {
        // Prepare columns
        $columnList = is_array($columns) ? implode(', ', $columns) : '*';
        
        // Start building query
        $query = "SELECT $columnList FROM " . $table;
        
        // Build WHERE clause if conditions exist
        $whereClause = [];
        $params = [];
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                if ($value === null) {
                    $whereClause[] = "$field IS NULL";
                } else {
                    $whereClause[] = "$field = ?";
                    $params[] = $value;
                }
            }
            $query .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        // Add ORDER BY if specified
        if (!empty($orderBy)) {
            $query .= " ORDER BY " . $orderBy;
        }
        
        // Add LIMIT and OFFSET if specified
        if ($limit > 0) {
            $query .= " LIMIT " . (int)$limit;
            if ($offset > 0) {
                $query .= " OFFSET " . (int)$offset;
            }
        }
        
        // Execute query
        $stmt = executeQuery($connection, $query, $params);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Data retrieval failed: " . $e->getMessage());
        throw new Exception('Failed to retrieve data from database.');
    }
}

/**
 * Count rows in a table with optional conditions
 * 
 * @internal This is an internal helper function, use the public functions instead
 * @param PDO $connection Database connection
 * @param string $table Table name
 * @param array $conditions Optional conditions
 * @return int Number of rows
 * @throws Exception if query fails
 */
function countTableRows($connection, $table, $conditions = []) {
    try {
        $query = "SELECT COUNT(*) as count FROM " . $table;
        
        // Build WHERE clause if conditions exist
        $params = [];
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                if ($value === null) {
                    $whereClause[] = "$field IS NULL";
                } else {
                    $whereClause[] = "$field = ?";
                    $params[] = $value;
                }
            }
            $query .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $stmt = executeQuery($connection, $query, $params);
        $result = $stmt->fetch();
        return (int)$result['count'];
        
    } catch (Exception $e) {
        error_log("Row count failed: " . $e->getMessage());
        throw new Exception('Failed to count rows in database.');
    }
}

/**
 * Execute a custom query with optional parameters
 * 
 * @internal This is an internal helper function, use the public functions instead
 * @param PDO $connection Database connection
 * @param string $query Custom SQL query
 * @param array $params Query parameters
 * @param bool $fetchAll Whether to fetch all results or just one row
 * @return mixed Query results
 * @throws Exception if query fails
 */
function customQuery($connection, $query, $params = [], $fetchAll = true) {
    try {
        $stmt = executeQuery($connection, $query, $params);
        return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
    } catch (Exception $e) {
        error_log("Custom query failed: " . $e->getMessage());
        throw new Exception('Failed to execute custom query.');
    }
}

/**
 * Get all records from a table
 * 
 * @param string $table Table name
 * @param array $columns Columns to retrieve (default: all)
 * @return array
 */
function getAll($table, $columns = ['*']) {
    try {
        checkRateLimit('select');
        $table = validateTableName($table);
        $columns = validateColumns($columns);
        
        $db = connectDatabase();
        return getDataFromTable($db, $table, [], $columns);
    } catch (Exception $e) {
        error_log("Security violation in getAll: " . $e->getMessage());
        throw new Exception('Invalid request parameters');
    }
}

/**
 * Get a single record by ID
 * 
 * @param string $table Table name
 * @param int $id ID of the record
 * @param array $columns Columns to retrieve (default: all)
 * @return array|false
 */
function getById($table, $id, $columns = ['*']) {
    $db = connectDatabase();
    $result = getDataFromTable($db, $table, ['id' => $id], $columns, '', 1);
    return !empty($result) ? $result[0] : false;
}

/**
 * Get records by a specific field value
 * 
 * @param string $table Table name
 * @param string $field Field name
 * @param mixed $value Field value
 * @param array $columns Columns to retrieve (default: all)
 * @return array
 */
function getBy($table, $field, $value, $columns = ['*']) {
    $db = connectDatabase();
    return getDataFromTable($db, $table, [$field => $value], $columns);
}

/**
 * Get records with pagination
 * 
 * @param string $table Table name
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $columns Columns to retrieve (default: all)
 * @param string $orderBy Order by clause (default: 'id DESC')
 * @return array ['data' => array, 'total' => int, 'pages' => int]
 */
function getPaginated($table, $page = 1, $perPage = 10, $columns = ['*'], $orderBy = 'id DESC') {
    $db = connectDatabase();
    $offset = ($page - 1) * $perPage;
    
    $data = getDataFromTable($db, $table, [], $columns, $orderBy, $perPage, $offset);
    $total = countTableRows($db, $table);
    $pages = ceil($total / $perPage);
    
    return [
        'data' => $data,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'per_page' => $perPage
    ];
}

/**
 * Search records in table
 * 
 * @param string $table Table name
 * @param array $searchColumns Columns to search in
 * @param string $searchTerm Term to search for
 * @param array $columns Columns to retrieve (default: all)
 * @return array
 */
function searchTable($table, $searchColumns, $searchTerm, $columns = ['*']) {
    $db = connectDatabase();
    $query = "SELECT " . implode(', ', $columns) . " FROM $table WHERE ";
    
    $conditions = [];
    $params = [];
    foreach ($searchColumns as $column) {
        $conditions[] = "$column LIKE ?";
        $params[] = "%$searchTerm%";
    }
    
    $query .= "(" . implode(' OR ', $conditions) . ")";
    return customQuery($db, $query, $params);
}

/**
 * Insert a new record
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false The last inserted ID or false on failure
 */
function insert($table, $data) {
    try {
        // Security checks
        checkRateLimit('insert');
        $table = validateTableName($table);
        $data = validateData($data);
        
        if (empty($data)) {
            throw new Exception('No valid data provided for insert');
        }
        
        $db = connectDatabase();
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO $table ($columns) VALUES ($values)";
        executeQuery($db, $query, array_values($data));
        
        $lastId = $db->lastInsertId();
        if (!$lastId) {
            throw new Exception('Failed to insert record');
        }
        
        return $lastId;
    } catch (Exception $e) {
        error_log("Insert failed: " . $e->getMessage());
        throw new Exception('Failed to insert record');
    }
}

/**
 * Update a record
 * 
 * @param string $table Table name
 * @param int $id ID of the record
 * @param array $data Associative array of column => value
 * @return bool Success or failure
 */
function update($table, $id, $data) {
    try {
        // Security checks
        checkRateLimit('update');
        $table = validateTableName($table);
        $data = validateData($data);
        
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception('Invalid ID provided');
        }
        
        if (empty($data)) {
            throw new Exception('No valid data provided for update');
        }
        
        $db = connectDatabase();
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        
        $query = "UPDATE $table SET " . implode(', ', $set) . " WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = executeQuery($db, $query, $params);
        if ($stmt->rowCount() === 0) {
            throw new Exception('No records were updated');
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Update failed: " . $e->getMessage());
        throw new Exception('Failed to update record');
    }
}

/**
 * Delete a record
 * 
 * @param string $table Table name
 * @param int $id ID of the record
 * @return bool Success or failure
 */
function delete($table, $id) {
    try {
        // Security checks
        checkRateLimit('delete');
        $table = validateTableName($table);
        
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception('Invalid ID provided');
        }
        
        $db = connectDatabase();
        $stmt = executeQuery($db, "DELETE FROM $table WHERE id = ?", [$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('No records were deleted');
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Delete failed: " . $e->getMessage());
        throw new Exception('Failed to delete record');
    }
}

/**
 * Advanced URL validation and sanitization
 * 
 * @param string $url URL to validate
 * @return string|false Sanitized URL or false if invalid
 */
function validateUrl($url) {
    // Remove any whitespace and convert to lowercase
    $url = trim(strtolower($url));
    
    // Validate URL structure
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Get URL components
    $parts = parse_url($url);
    
    // Verify required components
    if (!isset($parts['scheme']) || !isset($parts['host'])) {
        return false;
    }
    
    // Allow only http and https
    if (!in_array($parts['scheme'], ['http', 'https'])) {
        return false;
    }
    
    // Validate host (prevent IDN homograph attacks)
    if (preg_match('/[^\x20-\x7f]/', $parts['host'])) {
        return false;
    }
    
    return $url;
}

/**
 * Handle file upload with security checks
 * 
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @param string $uploadDir Upload directory path
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function handleFileUpload($file, $allowedTypes, $maxSize = 5242880, $uploadDir = 'uploads') {
    try {
        // Basic validation
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception('No file uploaded');
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit');
        }

        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Get file info
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Validate MIME type
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        // Check for malicious file contents
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<(?:php|script|iframe|object|applet|html|body|head|link|meta|style|base)/i', $content)) {
            throw new Exception('Potentially malicious file content detected');
        }

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }

        return [
            'success' => true,
            'path' => $destination,
            'error' => null
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'path' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Enhanced SSL/TLS configuration for database
 * 
 * @return array SSL configuration options
 */
function getEnhancedSSLConfig() {
    return [
        PDO::MYSQL_ATTR_SSL_KEY    => defined('DB_SSL_KEY') ? DB_SSL_KEY : null,
        PDO::MYSQL_ATTR_SSL_CERT   => defined('DB_SSL_CERT') ? DB_SSL_CERT : null,
        PDO::MYSQL_ATTR_SSL_CA     => defined('DB_SSL_CA') ? DB_SSL_CA : null,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::MYSQL_ATTR_SSL_CIPHER => 'DHE-RSA-AES256-SHA',
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_PERSISTENT => true
    ];
}

/**
 * Create a backup of the database
 * 
 * @param string $backupDir Directory to store backup
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function createDatabaseBackup($backupDir = 'backups') {
    try {
        // Create backup directory
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generate backup filename
        $filename = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --opt -h %s -u %s -p%s %s > %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASSWORD),
            escapeshellarg(DB_NAME),
            escapeshellarg($filename)
        );

        // Execute backup
        exec($command, $output, $return);

        if ($return !== 0) {
            throw new Exception('Database backup failed');
        }

        // Encrypt backup
        if (function_exists('sodium_crypto_secretbox')) {
            $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            
            $encrypted = sodium_crypto_secretbox(
                file_get_contents($filename),
                $nonce,
                $key
            );
            
            file_put_contents($filename . '.enc', $nonce . $encrypted);
            unlink($filename);
            $filename .= '.enc';
        }

        return [
            'success' => true,
            'path' => $filename,
            'error' => null
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'path' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Transaction wrapper for database operations
 * 
 * @param PDO $connection Database connection
 * @param callable $callback Function to execute within transaction
 * @return mixed Result of callback
 */
function withTransaction($connection, callable $callback) {
    try {
        $connection->beginTransaction();
        $result = $callback($connection);
        $connection->commit();
        return $result;
    } catch (Exception $e) {
        $connection->rollBack();
        throw $e;
    }
}

/**
 * Advanced query builder for complex queries
 */
class QueryBuilder {
    private $table;
    private $conditions = [];
    private $params = [];
    private $joins = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit = null;
    private $offset = null;

    public function __construct($table) {
        $this->table = validateTableName($table);
    }

    public function where($column, $operator, $value) {
        $this->conditions[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function join($table, $condition) {
        $this->joins[] = "JOIN " . validateTableName($table) . " ON $condition";
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = validateTableName($column) . " " . 
                          (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
        return $this;
    }

    public function groupBy($column) {
        $this->groupBy[] = validateTableName($column);
        return $this;
    }

    public function having($condition) {
        $this->having[] = $condition;
        return $this;
    }

    public function limit($limit, $offset = null) {
        $this->limit = (int)$limit;
        if ($offset !== null) {
            $this->offset = (int)$offset;
        }
        return $this;
    }

    public function execute() {
        $db = connectDatabase();
        $query = "SELECT * FROM " . $this->table;

        if ($this->joins) {
            $query .= " " . implode(" ", $this->joins);
        }

        if ($this->conditions) {
            $query .= " WHERE " . implode(" AND ", $this->conditions);
        }

        if ($this->groupBy) {
            $query .= " GROUP BY " . implode(", ", $this->groupBy);
        }

        if ($this->having) {
            $query .= " HAVING " . implode(" AND ", $this->having);
        }

        if ($this->orderBy) {
            $query .= " ORDER BY " . implode(", ", $this->orderBy);
        }

        if ($this->limit !== null) {
            $query .= " LIMIT " . $this->limit;
            if ($this->offset !== null) {
                $query .= " OFFSET " . $this->offset;
            }
        }

        return customQuery($db, $query, $this->params);
    }
}
