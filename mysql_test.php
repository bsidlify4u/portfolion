<?php
require_once __DIR__ . '/vendor/autoload.php';

use Portfolion\Database\QueryBuilder;

/**
 * Test database connectivity and configuration with detailed diagnostics
 */
function testDatabaseConnection() {
    echo "\nSystem Information:\n";
    echo "----------------\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Loaded Extensions: " . implode(", ", get_loaded_extensions()) . "\n";
    echo "PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
    
    // Check MySQL socket
    $socketFile = '/var/run/mysqld/mysqld.sock';
    echo "\nMySQL Socket:\n";
    echo "------------\n";
    if (file_exists($socketFile)) {
        $perms = fileperms($socketFile);
        $owner = posix_getpwuid(fileowner($socketFile));
        echo "Socket exists at: $socketFile\n";
        echo "Permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
        echo "Owner: " . $owner['name'] . "\n";
    } else {
        echo "Socket not found at: $socketFile\n";
    }

    try {
        echo "\nConnection Test:\n";
        echo "--------------\n";
        
        // Test basic connection
        $qb = new QueryBuilder();
        $conn = $qb->getConnection();
        echo "✓ Basic connection successful\n";
        
        // Get server info
        $serverVersion = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
        $serverInfo = $conn->getAttribute(PDO::ATTR_SERVER_INFO);
        echo "✓ MySQL Server Version: $serverVersion\n";
        echo "✓ Server Info: $serverInfo\n";
        
        // Test query execution
        $stmt = $conn->query('SELECT NOW() AS `time`');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Query execution successful. Current time: {$result['time']}\n";
        
        // Test database selection
        $result = $conn->query('SELECT DATABASE() as current_db')->fetch(PDO::FETCH_ASSOC);
        echo "✓ Current database: {$result['current_db']}\n";
        
        // Test character set
        $result = $conn->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch(PDO::FETCH_ASSOC);
        echo "✓ Database character set: {$result['Value']}\n";
        
        // Test user privileges
        echo "\nUser Privileges:\n";
        echo "---------------\n";
        $result = $conn->query("SHOW GRANTS")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($result as $grant) {
            echo "✓ $grant\n";
        }
        
        // Test persistent connection if enabled
        if ($conn->getAttribute(PDO::ATTR_PERSISTENT)) {
            echo "\nPersistent Connection:\n";
            echo "-------------------\n";
            echo "✓ Using persistent connection\n";
            echo "✓ Connection status: " . ($conn->getAttribute(PDO::ATTR_CONNECTION_STATUS) ?? 'Unknown') . "\n";
        }

        return true;
    } catch (Exception $e) {
        echo "\nError Details:\n";
        echo "-------------\n";
        echo "✗ Error Type: " . get_class($e) . "\n";
        echo "✗ Message: " . $e->getMessage() . "\n";
        echo "✗ Code: " . $e->getCode() . "\n";
        echo "✗ File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
        return false;
    }
}

// Run the test
echo "\nStarting MySQL Connection Test\n";
echo "==========================\n";
$success = testDatabaseConnection();
echo "\n==========================\n";
echo $success ? "All tests passed successfully!" : "Tests failed - see errors above";
echo "\n";
