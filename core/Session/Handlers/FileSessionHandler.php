<?php

namespace Portfolion\Session\Handlers;

use Portfolion\Config;
use RuntimeException;

/**
 * File-based session handler for the Portfolion framework
 * 
 * This class handles sessions stored in files.
 */
class FileSessionHandler extends AbstractSessionHandler
{
    /**
     * @var string The path where sessions are stored
     */
    protected string $path;
    
    /**
     * Create a new file session handler instance
     * 
     * @param Config $config Configuration instance
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        
        // Set the session path
        $this->path = $config->get('session.files', storage_path('framework/sessions'));
        
        // Ensure the session directory exists
        $this->ensureSessionDirectoryExists();
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
        // Use the configured path if no save path is provided
        if (!empty($savePath)) {
            $this->path = $savePath;
            $this->ensureSessionDirectoryExists();
        }
        
        return true;
    }
    
    /**
     * Read the session data
     * 
     * @param string $id The session ID
     * @return string|false The session data or false on failure
     */
    public function read($id)
    {
        $file = $this->getSessionFile($id);
        
        if (file_exists($file)) {
            $data = (string) file_get_contents($file);
            
            return $data;
        }
        
        return '';
    }
    
    /**
     * Write the session data
     * 
     * @param string $id The session ID
     * @param string $data The session data
     * @return bool Whether the operation was successful
     */
    public function write($id, $data): bool
    {
        $file = $this->getSessionFile($id);
        
        return file_put_contents($file, $data, LOCK_EX) !== false;
    }
    
    /**
     * Destroy a session
     * 
     * @param string $id The session ID
     * @return bool Whether the operation was successful
     */
    public function destroy($id): bool
    {
        $file = $this->getSessionFile($id);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * Garbage collection
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    public function gc($lifetime): bool
    {
        return $this->clean($lifetime);
    }
    
    /**
     * Check if a session exists
     * 
     * @param string $id The session ID
     * @return bool Whether the session exists
     */
    public function exists(string $id): bool
    {
        return file_exists($this->getSessionFile($id));
    }
    
    /**
     * Clean expired sessions
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    public function clean(int $lifetime): bool
    {
        $pattern = $this->path . DIRECTORY_SEPARATOR . 'sess_*';
        $time = time() - $lifetime;
        
        foreach (glob($pattern) as $file) {
            if (is_file($file) && filemtime($file) < $time) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Get the session file path
     * 
     * @param string $id The session ID
     * @return string The session file path
     */
    protected function getSessionFile(string $id): string
    {
        return $this->path . DIRECTORY_SEPARATOR . 'sess_' . $id;
    }
    
    /**
     * Ensure the session directory exists
     * 
     * @return void
     * @throws RuntimeException If the directory cannot be created or is not writable
     */
    protected function ensureSessionDirectoryExists(): void
    {
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0755, true) && !is_dir($this->path)) {
                throw new RuntimeException("Session directory [{$this->path}] could not be created.");
            }
        }
        
        if (!is_writable($this->path)) {
            throw new RuntimeException("Session directory [{$this->path}] is not writable.");
        }
    }
} 