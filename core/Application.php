<?php

namespace Portfolion;

use Portfolion\Core\Bootstrap;
use Portfolion\Container\Container;
use Portfolion\Config;

/**
 * Main application class that acts as the entry point for the framework
 */
class Application 
{
    /**
     * @var Bootstrap The bootstrap instance
     */
    protected $bootstrap;
    
    /**
     * @var Container The IoC container
     */
    protected $container;
    
    /**
     * @var Config The configuration instance
     */
    protected $config;
    
    /**
     * Create a new application instance
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
    }
    
    /**
     * Bootstrap the application
     * 
     * @return void
     */
    public function bootstrap(): void
    {
        // Set up environment for testing
        if (env('APP_ENV') === 'testing') {
            $this->setupTestingEnvironment();
        }
        
        // Initialize the bootstrap
        $this->bootstrap = Bootstrap::getInstance();
        $this->container = $this->bootstrap->getContainer();
        
        // Boot the application
        $this->bootstrap->boot();
    }
    
    /**
     * Set up the testing environment
     * 
     * @return void
     */
    protected function setupTestingEnvironment(): void
    {
        // Set testing-specific configuration
        putenv('CACHE_DRIVER=array');
        putenv('SESSION_DRIVER=array');
        putenv('QUEUE_DRIVER=sync');
        
        // Handle database setup based on the configured driver
        $dbConnection = env('DB_CONNECTION', 'sqlite');
        
        switch ($dbConnection) {
            case 'sqlite':
                $dbPath = env('DB_DATABASE');
                
                // Skip file setup for in-memory database
                if ($dbPath === ':memory:') {
                    return;
                }
                
                // Create the SQLite database file if needed
                if (!file_exists($dbPath)) {
                    $directory = dirname($dbPath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    touch($dbPath);
                }
                break;
                
            case 'mysql':
            case 'mariadb':
            case 'pgsql':
            case 'sqlsrv':
            case 'oracle':
            case 'oci':
                // These database engines require external setup
                // The test environment should have a dedicated test database created
                
                // Optionally check connection here to provide helpful error messages
                try {
                    $connection = new \Portfolion\Database\Connection($dbConnection);
                    $connection->getPdo()->query('SELECT 1');
                } catch (\PDOException $e) {
                    echo "Warning: Could not connect to test database. " . 
                         "Make sure a '{$dbConnection}' test database is configured.\n";
                    echo "Error: " . $e->getMessage() . "\n";
                }
                break;
        }
    }
    
    /**
     * Get the bootstrap instance
     * 
     * @return Bootstrap
     */
    public function getBootstrap(): Bootstrap
    {
        return $this->bootstrap;
    }
    
    /**
     * Get the IoC container
     * 
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get the configuration instance
     * 
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
} 