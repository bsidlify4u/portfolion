<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/core/Config.php';

require __DIR__ . '/vendor/autoload.php';

use Portfolion\Database\Connection;

// Create a new connection
$connection = new Connection();

// Get the driver and PDO instance
$driver = $connection->getDriver();
$pdo = $connection->getPdo();

echo "Database driver: {$driver}\n";

// Get all tables
$tables = [];

switch ($driver) {
    case 'sqlite':
        $query = "SELECT name FROM sqlite_master WHERE type='table'";
        $stmt = $pdo->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tables[] = $row['name'];
        }
        break;
        
    case 'mysql':
    case 'mariadb':
        $query = "SHOW TABLES";
        $stmt = $pdo->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        break;
        
    default:
        echo "Driver not supported in this test script.\n";
        exit;
}

echo "Tables in the database:\n";
foreach ($tables as $table) {
    echo "- {$table}\n";
}

// Check if migrations table exists
if (in_array('migrations', $tables)) {
    echo "\nMigrations in the database:\n";
    $query = "SELECT * FROM migrations";
    $stmt = $pdo->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['id']}: {$row['migration']} (Batch: {$row['batch']})\n";
    }
}

// Check if users table exists
if (in_array('users', $tables)) {
    echo "\nUsers table structure:\n";
    if ($driver === 'sqlite') {
        $query = "PRAGMA table_info(users)";
        $stmt = $pdo->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['name']} ({$row['type']})\n";
        }
    } elseif ($driver === 'mysql' || $driver === 'mariadb') {
        $query = "DESCRIBE users";
        $stmt = $pdo->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    }
}
