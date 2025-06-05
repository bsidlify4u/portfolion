<?php

namespace Portfolion\Session;

/**
 * Extended SessionHandlerInterface for the Portfolion framework
 * 
 * This interface extends PHP's SessionHandlerInterface with additional methods
 * for session management.
 */
interface SessionHandlerInterface extends \SessionHandlerInterface
{
    /**
     * Get the session handler name
     * 
     * @return string The handler name
     */
    public function getName(): string;
    
    /**
     * Check if a session exists
     * 
     * @param string $id The session ID
     * @return bool Whether the session exists
     */
    public function exists(string $id): bool;
    
    /**
     * Clean expired sessions
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    public function clean(int $lifetime): bool;
} 