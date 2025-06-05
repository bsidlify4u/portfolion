<?php

namespace Portfolion\Session;

use Portfolion\Config;
use RuntimeException;

/**
 * Session manager for the Portfolion framework
 * 
 * This class manages session handlers and provides a factory for creating
 * session instances with different storage drivers.
 */
class SessionManager
{
    /**
     * @var array Available session drivers
     */
    protected array $drivers = [
        'file' => Handlers\FileSessionHandler::class,
        'database' => Handlers\DatabaseSessionHandler::class,
        'redis' => Handlers\RedisSessionHandler::class,
        'memcached' => Handlers\MemcachedSessionHandler::class,
    ];
    
    /**
     * @var Config Configuration instance
     */
    protected Config $config;
    
    /**
     * @var array Active session handlers
     */
    protected array $handlers = [];
    
    /**
     * Create a new session manager instance
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
    }
    
    /**
     * Create a session handler for the specified driver
     * 
     * @param string|null $driver The driver name (null for default)
     * @return \SessionHandlerInterface The session handler
     * @throws RuntimeException If the driver is not supported
     */
    public function driver(?string $driver = null): \SessionHandlerInterface
    {
        $driver = $driver ?? $this->getDefaultDriver();
        
        // Return existing handler if already created
        if (isset($this->handlers[$driver])) {
            return $this->handlers[$driver];
        }
        
        // Create a new handler
        $handler = $this->createDriver($driver);
        
        // Register the handler
        session_set_save_handler($handler, true);
        
        // Store the handler
        $this->handlers[$driver] = $handler;
        
        return $handler;
    }
    
    /**
     * Create a session handler for the specified driver
     * 
     * @param string $driver The driver name
     * @return \SessionHandlerInterface The session handler
     * @throws RuntimeException If the driver is not supported
     */
    protected function createDriver(string $driver): \SessionHandlerInterface
    {
        // Check if the driver is supported
        if (!isset($this->drivers[$driver])) {
            throw new RuntimeException("Session driver [{$driver}] is not supported.");
        }
        
        // Get the handler class
        $handlerClass = $this->drivers[$driver];
        
        // Check if the handler class exists
        if (!class_exists($handlerClass)) {
            throw new RuntimeException("Session handler class [{$handlerClass}] does not exist.");
        }
        
        // Create the handler
        return new $handlerClass($this->config);
    }
    
    /**
     * Get the default session driver
     * 
     * @return string The default driver
     */
    protected function getDefaultDriver(): string
    {
        return $this->config->get('session.driver', 'file');
    }
    
    /**
     * Register a custom session driver
     * 
     * @param string $driver The driver name
     * @param string $handler The handler class
     * @return void
     */
    public function registerDriver(string $driver, string $handler): void
    {
        $this->drivers[$driver] = $handler;
    }
    
    /**
     * Start the session
     * 
     * @return bool Whether the session was started
     */
    public function start(): bool
    {
        // Get the driver
        $driver = $this->getDefaultDriver();
        
        // Create the handler
        $this->driver($driver);
        
        // Start the session
        return Session::getInstance()->start();
    }
} 