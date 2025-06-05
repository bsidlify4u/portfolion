<?php

/**
 * Database connection test script for Portfolion
 * 
 * This script tests connections to all configured database engines.
 * Usage: php scripts/test-db-connection.php [driver]
 */

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Get configuration
$config = require_once __DIR__ . '/../config/database.php';

// Get the driver from command line arguments
$testDriver = $argv[1] ?? null;

echo "Portfolion Database Connection Tester\n";
echo "====================================\n\n";

// Function to test a specific database connection
function testConnection(string $driver, array $config): void 
{
    echo "Testing {$driver} connection...\n";
    
    $connectionConfig = $config['connections'][$driver] ?? null;
    
    if (!$connectionConfig) {
        echo "  [ERROR] No configuration found for {$driver}\n";
        return;
    }
    
    try {
        // Create a connection instance
        $connection = new \Portfolion\Database\Connection($driver, $config);
        $pdo = $connection->getPdo();
        
        // Test the connection with a simple query
        $pdo->query('SELECT 1');
        
        // Get the server info
        $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        
        echo "  [SUCCESS] Connected to {$driver} server version: {$serverInfo}\n";
        
        // Test database features based on driver
        echo "  Testing features:\n";
        
        // Test creating a simple table
        $tableName = "test_" . time();
        try {
            // Drop table if exists
            $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
            
            // Create table with driver-specific syntax
            switch ($driver) {
                case 'sqlite':
                    $pdo->exec("CREATE TABLE {$tableName} (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
                    break;
                    
                case 'mysql':
                case 'mariadb':
                    $pdo->exec("CREATE TABLE {$tableName} (id BIGINT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255)) ENGINE=InnoDB");
                    break;
                    
                case 'pgsql':
                    $pdo->exec("CREATE TABLE {$tableName} (id SERIAL PRIMARY KEY, name VARCHAR(255))");
                    break;
                    
                case 'sqlsrv':
                    $pdo->exec("CREATE TABLE {$tableName} (id BIGINT IDENTITY(1,1) PRIMARY KEY, name NVARCHAR(255))");
                    break;
                    
                case 'oracle':
                case 'oci':
                    $pdo->exec("CREATE TABLE {$tableName} (id NUMBER(19) PRIMARY KEY, name VARCHAR2(255))");
                    $pdo->exec("CREATE SEQUENCE {$tableName}_seq START WITH 1");
                    $pdo->exec("
                    CREATE OR REPLACE TRIGGER {$tableName}_trg
                    BEFORE INSERT ON {$tableName}
                    FOR EACH ROW
                    BEGIN
                        IF :new.id IS NULL THEN
                            SELECT {$tableName}_seq.NEXTVAL INTO :new.id FROM dual;
                        END IF;
                    END;
                    ");
                    break;
            }
            
            echo "    - Created test table: {$tableName}\n";
            
            // Insert a test record
            $stmt = $pdo->prepare("INSERT INTO {$tableName} (name) VALUES (?)");
            $stmt->execute(['Test record']);
            
            // Get the last insert ID
            $lastId = $driver === 'pgsql' 
                ? $pdo->query("SELECT currval(pg_get_serial_sequence('{$tableName}', 'id'))")->fetchColumn() 
                : $pdo->lastInsertId();
                
            echo "    - Inserted test record with ID: {$lastId}\n";
            
            // Query the record
            $stmt = $pdo->prepare("SELECT * FROM {$tableName} WHERE id = ?");
            $stmt->execute([$lastId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "    - Retrieved record: " . json_encode($row) . "\n";
            
            // Clean up
            $pdo->exec("DROP TABLE {$tableName}");
            
            if ($driver === 'oracle' || $driver === 'oci') {
                $pdo->exec("DROP SEQUENCE {$tableName}_seq");
            }
            
            echo "    - Cleaned up test table\n";
            
        } catch (PDOException $e) {
            echo "    [ERROR] Feature test failed: " . $e->getMessage() . "\n";
        }
        
    } catch (PDOException $e) {
        echo "  [ERROR] Connection failed: " . $e->getMessage() . "\n";
        
        // Show connection details for debugging (except password)
        $connectionDetails = $connectionConfig;
        if (isset($connectionDetails['password'])) {
            $connectionDetails['password'] = '********';
        }
        
        echo "  Connection details: " . json_encode($connectionDetails, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
}

// If a specific driver is requested, test only that one
if ($testDriver) {
    if (isset($config['connections'][$testDriver])) {
        testConnection($testDriver, $config);
    } else {
        echo "Error: Unknown driver '{$testDriver}'\n";
        echo "Available drivers: " . implode(', ', array_keys($config['connections'])) . "\n";
    }
} else {
    // Otherwise test all configured connections
    foreach (array_keys($config['connections']) as $driver) {
        testConnection($driver, $config);
    }
}

echo "Database connection tests completed.\n"; 