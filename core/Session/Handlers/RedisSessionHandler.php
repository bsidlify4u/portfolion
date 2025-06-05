<?php

namespace Portfolion\Session\Handlers;

use Portfolion\Config;
use RuntimeException;
use Redis;

/**
 * Redis-based session handler for the Portfolion framework
 * 
 * This class handles sessions stored in Redis.
 */
class RedisSessionHandler extends AbstractSessionHandler
{
    /**
     * @var Redis Redis connection
     */
    protected Redis $redis;
    
    /**
     * @var string Redis key prefix
     */
    protected string $prefix;
    
    /**
     * Create a new Redis session handler instance
     * 
     * @param Config $config Configuration instance
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        
        // Set the key prefix
        $this->prefix = $config->get('session.prefix', 'portfolion_session:');
        
        // Create the Redis connection
        $this->redis = $this->createConnection();
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
        return $this->redis->isConnected();
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
    public function read($id)
    {
        $data = $this->redis->get($this->prefix . $id);
        
        return $data !== false ? $data : '';
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
        $lifetime = (int) ini_get('session.gc_maxlifetime');
        
        return $this->redis->setex($this->prefix . $id, $lifetime, $data);
    }
    
    /**
     * Destroy a session
     * 
     * @param string $id The session ID
     * @return bool Whether the operation was successful
     */
    public function destroy($id): bool
    {
        $this->redis->del($this->prefix . $id);
        
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
        // Redis automatically expires keys, so no need for garbage collection
        return true;
    }
    
    /**
     * Check if a session exists
     * 
     * @param string $id The session ID
     * @return bool Whether the session exists
     */
    public function exists(string $id): bool
    {
        return (bool) $this->redis->exists($this->prefix . $id);
    }
    
    /**
     * Clean expired sessions
     * 
     * @param int $lifetime The session lifetime in seconds
     * @return bool Whether the operation was successful
     */
    public function clean(int $lifetime): bool
    {
        // Redis automatically expires keys, so no need for cleaning
        return true;
    }
    
    /**
     * Create a Redis connection
     * 
     * @return Redis The Redis connection
     * @throws RuntimeException If the connection cannot be created
     */
    protected function createConnection(): Redis
    {
        $redis = new Redis();
        
        $host = $this->config->get('redis.host', '127.0.0.1');
        $port = $this->config->get('redis.port', 6379);
        $timeout = $this->config->get('redis.timeout', 0.0);
        $password = $this->config->get('redis.password');
        $database = $this->config->get('redis.database', 0);
        
        try {
            if (!$redis->connect($host, $port, $timeout)) {
                throw new RuntimeException("Could not connect to Redis server at {$host}:{$port}");
            }
            
            if ($password !== null) {
                if (!$redis->auth($password)) {
                    throw new RuntimeException('Failed to authenticate with Redis server');
                }
            }
            
            if ($database !== 0) {
                if (!$redis->select($database)) {
                    throw new RuntimeException("Failed to select Redis database {$database}");
                }
            }
            
            return $redis;
        } catch (\Exception $e) {
            throw new RuntimeException('Redis connection failed: ' . $e->getMessage());
        }
    }
} 