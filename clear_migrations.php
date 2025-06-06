<?php

// Simple script to clear the migrations table

// Bootstrap the application
require_once 'bootstrap.php';

// Get database connection
try {
    $connection = new Portfolion\Database\Connection();
    $pdo = $connection->getPdo();
    
    // Drop the migrations table
    $pdo->exec('DROP TABLE IF EXISTS migrations');
    
    echo "Migrations table dropped successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 