<?php

// Simple script to fix migrations table

// Bootstrap the application
require_once 'bootstrap.php';

// Get database connection
$connection = new Portfolion\Database\Connection();
$pdo = $connection->getPdo();

// Check if migrations table exists
try {
    $result = $pdo->query("SELECT COUNT(*) FROM migrations");
    echo "Migrations table exists.\n";
    
    // Check for problematic records
    $stmt = $pdo->query("SELECT * FROM migrations WHERE migration = 'Table'");
    $problematicRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($problematicRecords)) {
        echo "Found " . count($problematicRecords) . " problematic migration records.\n";
        
        // Delete problematic records
        $deleteStmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $deleteStmt->execute(['Table']);
        
        echo "Deleted problematic records.\n";
    } else {
        echo "No problematic records found.\n";
    }
    
    // List all migrations
    $stmt = $pdo->query("SELECT * FROM migrations ORDER BY id");
    echo "\nCurrent migrations:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID: {$row['id']}, Migration: {$row['migration']}, Batch: {$row['batch']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone.\n"; 