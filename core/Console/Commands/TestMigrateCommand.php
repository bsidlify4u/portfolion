<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;
use Portfolion\Database\DB;

class TestMigrateCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'test:migrate';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Run migrations for the testing database';
    
    /**
     * Define command options - we include all options from TestCommand to avoid errors
     * when they're passed through
     */
    protected $options = [
        'filter' => 'Filter which tests to run',
        'group' => 'Run tests in the specified group',
        'stop-on-failure' => 'Stop execution upon first error or failure',
        'coverage' => 'Generate code coverage report',
        'skip-migrations' => 'Skip running migrations before tests'
    ];

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Setting Up Test Database Schema');
        
        // Ensure we're using the testing environment
        putenv('APP_ENV=testing');
        
        try {
            // Get database connection
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $driver = $connection->getDriver();
            
            $io->info("Using database driver: $driver");
            
            // Create tasks table
            $io->section('Creating tasks table');
            $pdo->exec("DROP TABLE IF EXISTS tasks");
            
            // Use appropriate SQL syntax based on the database driver
            if ($driver === 'sqlite') {
                $pdo->exec("
                    CREATE TABLE tasks (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        title VARCHAR(255) NOT NULL,
                        description TEXT,
                        status VARCHAR(50) DEFAULT 'pending',
                        due_date DATE,
                        created_at DATETIME,
                        updated_at DATETIME
                    )
                ");
            } else {
                // MySQL/MariaDB syntax
                $pdo->exec("
                    CREATE TABLE tasks (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        description TEXT,
                        status VARCHAR(50) DEFAULT 'pending',
                        due_date DATE,
                        created_at DATETIME,
                        updated_at DATETIME
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            // Create users table
            $io->section('Creating users table');
            $pdo->exec("DROP TABLE IF EXISTS users");
            
            if ($driver === 'sqlite') {
                $pdo->exec("
                    CREATE TABLE users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        created_at DATETIME,
                        updated_at DATETIME
                    )
                ");
            } else {
                // MySQL/MariaDB syntax
                $pdo->exec("
                    CREATE TABLE users (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        created_at DATETIME,
                        updated_at DATETIME
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            // Insert test data
            $io->section('Inserting test data');
            
            // Use appropriate datetime function based on the database driver
            $dateFunc = ($driver === 'sqlite') ? "datetime('now')" : "NOW()";
            
            // Insert tasks
            $pdo->exec("
                INSERT INTO tasks (title, description, status, due_date, created_at, updated_at)
                VALUES 
                ('Test Task 1', 'Description for task 1', 'pending', '2023-12-31', {$dateFunc}, {$dateFunc}),
                ('Test Task 2', 'Description for task 2', 'completed', '2023-12-25', {$dateFunc}, {$dateFunc}),
                ('Test Task 3', 'Description for task 3', 'in-progress', '2024-01-15', {$dateFunc}, {$dateFunc})
            ");
            
            // Insert users
            $pdo->exec("
                INSERT INTO users (name, email, password, created_at, updated_at)
                VALUES 
                ('Test User', 'test@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', {$dateFunc}, {$dateFunc}),
                ('Admin User', 'admin@example.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', {$dateFunc}, {$dateFunc})
            ");
            
            $io->success('Test database schema created successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to set up test database: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 