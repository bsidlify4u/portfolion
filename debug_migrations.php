<?php

// Debug script to diagnose migration issues

// Check database connection and migrations table
echo "Checking database connection...\n";
try {
    // Try to connect to the database
    require_once 'core/Database/Connection.php';
    $connection = new Portfolion\Database\Connection();
    echo "  Connection successful\n";
    
    // Check if migrations table exists
    $driver = $connection->getDriver();
    echo "  Database driver: {$driver}\n";
    
    $pdo = $connection->getPdo();
    
    // Create migrations table if it doesn't exist
    $query = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL
        )
    ";
    
    try {
        $pdo->exec($query);
        echo "  Migrations table created or already exists\n";
    } catch (PDOException $e) {
        echo "  Failed to create migrations table: " . $e->getMessage() . "\n";
    }
    
    // Query to check if migrations table exists (generic approach)
    $query = "SELECT COUNT(*) FROM migrations";
    try {
        $result = $pdo->query($query);
        $count = $result->fetchColumn();
        echo "  Migrations table exists with {$count} records\n";
        
        // List all migrations in the table
        $query = "SELECT * FROM migrations ORDER BY id";
        $stmt = $pdo->query($query);
        echo "  Migrations in the database:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "    - ID: {$row['id']}, Migration: {$row['migration']}, Batch: {$row['batch']}\n";
            
            // Check if this migration is causing issues
            if ($row['migration'] === 'Table') {
                echo "    - FOUND PROBLEMATIC MIGRATION: Table\n";
                
                // Delete this problematic record
                $deleteQuery = "DELETE FROM migrations WHERE id = ?";
                $deleteStmt = $pdo->prepare($deleteQuery);
                $deleteStmt->execute([$row['id']]);
                echo "    - DELETED problematic migration record\n";
            }
        }
    } catch (PDOException $e) {
        echo "  Migrations table check failed: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "  Connection failed: " . $e->getMessage() . "\n";
}

echo "\nDebug completed.\n"; 