<?php

namespace Portfolion\Session;

use Portfolion\Config;

/**
 * Session management class for the Portfolion framework
 * 
 * This class provides methods for working with PHP sessions in a secure
 * and consistent way across the application.
 */
class Session
{
    /**
     * @var bool Whether the session has been started
     */
    private bool $started = false;
    
    /**
     * @var array Session configuration
     */
    private array $config;
    
    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $config = Config::getInstance();
        $this->config = $config->get('session', []);
    }
    
    /**
     * Get the singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start the session
     * 
     * @return bool Whether the session was started
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        
        // Configure session settings
        $this->configure();
        
        // Start the session
        $this->started = session_start();
        
        // Regenerate ID if needed
        $this->checkSessionIdLifetime();
        
        return $this->started;
    }
    
    /**
     * Configure session settings
     * 
     * @return void
     */
    private function configure(): void
    {
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'] ?? 120 * 60, // in seconds
            'path' => $this->config['path'] ?? '/',
            'domain' => $this->config['domain'] ?? null,
            'secure' => $this->config['secure'] ?? true,
            'httponly' => $this->config['http_only'] ?? true,
            'samesite' => $this->config['same_site'] ?? 'Lax'
        ]);
        
        // Set session name
        if (!empty($this->config['cookie'])) {
            session_name($this->config['cookie']);
        }
        
        // Set session save path if using file driver
        if (($this->config['driver'] ?? 'file') === 'file' && !empty($this->config['files'])) {
            session_save_path($this->config['files']);
        }
    }
    
    /**
     * Check if the session ID needs to be regenerated
     * 
     * @return void
     */
    private function checkSessionIdLifetime(): void
    {
        // Get the last time the session ID was regenerated
        $lastRegenerated = $_SESSION['_last_regenerated'] ?? 0;
        
        // Regenerate ID if it's been too long (30 minutes by default)
        $lifetime = $this->config['id_lifetime'] ?? 1800;
        if (time() - $lastRegenerated > $lifetime) {
            $this->regenerateId();
        }
    }
    
    /**
     * Regenerate the session ID
     * 
     * @param bool $deleteOldSession Whether to delete the old session
     * @return bool Whether the ID was regenerated
     */
    public function regenerateId(bool $deleteOldSession = true): bool
    {
        if (!$this->started) {
            $this->start();
        }
        
        // Regenerate session ID
        $result = session_regenerate_id($deleteOldSession);
        
        // Update last regenerated timestamp
        $_SESSION['_last_regenerated'] = time();
        
        return $result;
    }
    
    /**
     * Get a value from the session
     * 
     * @param string $key The key to get
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed The value or default
     */
    public function get(string $key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set a value in the session
     * 
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return void
     */
    public function set(string $key, $value): void
    {
        if (!$this->started) {
            $this->start();
        }
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if a key exists in the session
     * 
     * @param string $key The key to check
     * @return bool Whether the key exists
     */
    public function has(string $key): bool
    {
        if (!$this->started) {
            $this->start();
        }
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove a value from the session
     * 
     * @param string $key The key to remove
     * @return void
     */
    public function remove(string $key): void
    {
        if (!$this->started) {
            $this->start();
        }
        
        unset($_SESSION[$key]);
    }
    
    /**
     * Get all session data
     * 
     * @return array All session data
     */
    public function all(): array
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $_SESSION;
    }
    
    /**
     * Flash a value to the session for the next request only
     * 
     * @param string $key The key to flash
     * @param mixed $value The value to flash
     * @return void
     */
    public function flash(string $key, $value): void
    {
        if (!$this->started) {
            $this->start();
        }
        
        // Store in flash data
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get a flashed value from the session
     * 
     * @param string $key The key to get
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed The value or default
     */
    public function getFlash(string $key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $_SESSION['_flash'][$key] ?? $default;
    }
    
    /**
     * Check if a flashed value exists in the session
     * 
     * @param string $key The key to check
     * @return bool Whether the key exists
     */
    public function hasFlash(string $key): bool
    {
        if (!$this->started) {
            $this->start();
        }
        
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Clear all flashed data
     * 
     * @return void
     */
    public function clearFlash(): void
    {
        if (!$this->started) {
            $this->start();
        }
        
        $_SESSION['_flash'] = [];
    }
    
    /**
     * Clear all session data
     * 
     * @return void
     */
    public function clear(): void
    {
        if (!$this->started) {
            $this->start();
        }
        
        session_unset();
    }
    
    /**
     * Destroy the session
     * 
     * @return bool Whether the session was destroyed
     */
    public function destroy(): bool
    {
        if (!$this->started) {
            return true;
        }
        
        // Clear session data
        $this->clear();
        
        // Destroy the session
        $result = session_destroy();
        
        // Reset started flag
        $this->started = false;
        
        return $result;
    }
    
    /**
     * Get the session ID
     * 
     * @return string The session ID
     */
    public function getId(): string
    {
        return session_id();
    }
    
    /**
     * Set the session ID
     * 
     * @param string $id The session ID
     * @return void
     */
    public function setId(string $id): void
    {
        session_id($id);
    }
    
    /**
     * Get the session name
     * 
     * @return string The session name
     */
    public function getName(): string
    {
        return session_name();
    }
    
    /**
     * Set the session name
     * 
     * @param string $name The session name
     * @return void
     */
    public function setName(string $name): void
    {
        session_name($name);
    }
} 