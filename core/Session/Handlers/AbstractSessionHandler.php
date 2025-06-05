<?php

namespace Portfolion\Session\Handlers;

use Portfolion\Config;
use Portfolion\Session\SessionHandlerInterface;

/**
 * Abstract session handler for the Portfolion framework
 * 
 * This class provides a base implementation for session handlers.
 */
abstract class AbstractSessionHandler implements SessionHandlerInterface
{
    /**
     * @var Config Configuration instance
     */
    protected Config $config;
    
    /**
     * @var string Handler name
     */
    protected string $name;
    
    /**
     * Create a new session handler instance
     * 
     * @param Config $config Configuration instance
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->name = static::class;
    }
    
    /**
     * Get the session handler name
     * 
     * @return string The handler name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Initialize the session handler
     * 
     * @param string $savePath The path where to store/retrieve the session
     * @param string $sessionName The session name
     * @return bool Whether initialization was successful
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }
    
    /**
     * Close the session
     * 
     * @return bool Whether the operation was successful
     */
    public function close(): bool
    {
        return true;
    }
    
    /**
     * Read the session data
     * 
     * @param string $id The session ID
     * @return string|false The session data or false on failure
     */
    abstract public function read($id);
    
    /**
     * Write the session data
     * 
     * @param string $id The session ID
     * @param string $data The session data
     * @return bool Whether the operation was successful
     */
    abstract public function write($id, $data);
    
    /**
     * Destroy a session
     * 
     * @param string $id The session ID
     * @return bool Whether the operation was successful
     */
    abstract public function destroy($id);
    
    /**
     * Garbage collection
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    abstract public function gc($lifetime);
    
    /**
     * Check if a session exists
     * 
     * @param string $id The session ID
     * @return bool Whether the session exists
     */
    abstract public function exists(string $id): bool;
    
    /**
     * Clean expired sessions
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    abstract public function clean(int $lifetime): bool;
} 