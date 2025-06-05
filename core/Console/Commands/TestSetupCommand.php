<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestSetupCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'test:setup';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Set up the testing environment';

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Setting Up Testing Environment');
        
        // Create .env.testing file
        $envPath = base_path('.env.testing');
        
        // Create testing environment config
        $content = <<<'EOT'
APP_NAME=Portfolion
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=storage/database/testing.sqlite

CACHE_DRIVER=array
SESSION_DRIVER=array
SESSION_LIFETIME=120

LOG_LEVEL=debug

OPCACHE_ENABLE=false
QUERY_CACHE=false
EOT;
        
        file_put_contents($envPath, $content);
        $io->success('.env.testing file created successfully.');
        
        // Create database directory if it doesn't exist
        $dbDir = base_path('storage/database');
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
            $io->info('Created database directory.');
        }
        
        // Create empty database file if it doesn't exist
        $dbFile = $dbDir . '/testing.sqlite';
        if (!file_exists($dbFile)) {
            touch($dbFile);
            $io->info('Created testing database file.');
        }
        
        // Create phpunit bootstrap file if it doesn't exist
        $bootstrapPath = base_path('tests/bootstrap.php');
        if (!file_exists($bootstrapPath)) {
            $bootstrapContent = <<<'EOT'
<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up testing environment
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . __DIR__ . '/../storage/database/testing.sqlite');

// Create database directory if it doesn't exist
$dbDir = __DIR__ . '/../storage/database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Create empty database file if it doesn't exist
$dbFile = $dbDir . '/testing.sqlite';
if (!file_exists($dbFile)) {
    touch($dbFile);
}

// Initialize the application for testing
$app = new Portfolion\Application();
$app->bootstrap();

// Set up database connection
$db = Portfolion\Database\DB::connection();

// Additional test setup can be added here
EOT;
            
            file_put_contents($bootstrapPath, $bootstrapContent);
            $io->success('Bootstrap file created successfully.');
        }
        
        // Create or update phpunit.xml
        $phpunitPath = base_path('phpunit.xml');
        $phpunitContent = <<<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Http">
            <directory suffix="Test.php">./tests/Http</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value="storage/database/testing.sqlite"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
EOT;
        
        file_put_contents($phpunitPath, $phpunitContent);
        $io->success('PHPUnit configuration updated.');
        
        $io->info('Testing environment is ready. Run tests with: ./vendor/bin/phpunit');
        
        return Command::SUCCESS;
    }
} 