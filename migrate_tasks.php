<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions/helpers.php';

// Get database configuration
$config = require_once config_path('database.php');

// Connect to the database
$connection = $config['default'];
$options = $config['connections'][$connection];

if ($connection === 'mysql') {
    $host = $options['host'];
    $port = $options['port'];
    $database = $options['database'];
    $username = $options['username'];
    $password = $options['password'];
    
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$database}",
            $username,
            $password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tasks table
        echo "Creating tasks table...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
                priority INT DEFAULT 0,
                due_date TIMESTAMP NULL,
                user_id INT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        echo "Tasks table created successfully!\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} 