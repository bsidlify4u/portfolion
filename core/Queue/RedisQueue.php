<?php

namespace Portfolion\Queue;

use Redis;
use RuntimeException;

/**
 * Redis queue driver for the Portfolion framework
 */
class RedisQueue implements QueueInterface
{
    /**
     * @var Redis Redis connection
     */
    protected Redis $redis;
    
    /**
     * @var string Key prefix
     */
    protected string $prefix;
    
    /**
     * @var array Configuration
     */
    protected array $config;
    
    /**
     * Create a new Redis queue instance
     * 
     * @param mixed $config Configuration
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->prefix = $config->get('queue.prefix', 'portfolion_queue:');
        
        // Create the Redis connection
        $this->redis = $this->createConnection();
    }
    
    /**
     * Push a job onto the queue
     * 
     * @param array $job The job to push
     * @return bool Whether the operation was successful
     */
    public function push(array $job): bool
    {
        $queue = $job['queue'];
        $payload = json_encode($job);
        
        try {
            // Push the job onto the queue
            $this->redis->rPush($this->getQueueKey($queue), $payload);
            
            // Add the queue to the set of known queues
            $this->redis->sAdd($this->prefix . 'queues', $queue);
            
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Pop a job off the queue
     * 
     * @param string $queue The queue to pop from
     * @return mixed The job or null
     */
    public function pop(string $queue)
    {
        try {
            // Pop a job from the queue
            $payload = $this->redis->lPop($this->getQueueKey($queue));
            
            if (!$payload) {
                return null;
            }
            
            $job = json_decode($payload, true);
            $job['id'] = md5($payload . uniqid('', true));
            $job['attempts'] = ($job['attempts'] ?? 0) + 1;
            $job['reserved_at'] = time();
            
            // Add the job to the reserved set
            $this->redis->setex(
                $this->prefix . 'reserved:' . $job['id'],
                60, // 1 minute timeout
                $payload
            );
            
            return $job;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete a job from the queue
     * 
     * @param mixed $job The job to delete
     * @return bool Whether the operation was successful
     */
    public function delete($job): bool
    {
        try {
            // Delete the job from the reserved set
            $this->redis->del($this->prefix . 'reserved:' . $job['id']);
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Release a job back onto the queue
     * 
     * @param mixed $job The job to release
     * @param int $delay The delay in seconds
     * @return bool Whether the operation was successful
     */
    public function release($job, int $delay = 0): bool
    {
        try {
            // Delete the job from the reserved set
            $this->redis->del($this->prefix . 'reserved:' . $job['id']);
            
            // Update the job
            $job['reserved_at'] = null;
            $job['available_at'] = time() + $delay;
            
            // Push the job back onto the queue
            return $this->push($job);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Push multiple jobs onto the queue
     * 
     * @param array $jobs The jobs to push
     * @return bool Whether the operation was successful
     */
    public function bulk(array $jobs): bool
    {
        try {
            $this->redis->multi();
            
            foreach ($jobs as $job) {
                $this->push($job);
            }
            
            $this->redis->exec();
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the size of a queue
     * 
     * @param string $queue The queue name
     * @return int The queue size
     */
    public function size(string $queue): int
    {
        try {
            return $this->redis->lLen($this->getQueueKey($queue));
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear a queue
     * 
     * @param string $queue The queue name
     * @return bool Whether the operation was successful
     */
    public function clear(string $queue): bool
    {
        try {
            $this->redis->del($this->getQueueKey($queue));
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the key for a queue
     * 
     * @param string $queue The queue name
     * @return string The queue key
     */
    protected function getQueueKey(string $queue): string
    {
        return $this->prefix . 'queue:' . $queue;
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