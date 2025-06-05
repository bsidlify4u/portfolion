<?php

namespace Portfolion\Queue;

use Portfolion\Config;

/**
 * Base job class for the Portfolion framework
 */
abstract class Job
{
    /**
     * @var int The number of times the job may be attempted
     */
    public $tries = 3;
    
    /**
     * @var int The number of seconds to wait before retrying the job
     */
    public $retryAfter = 60;
    
    /**
     * @var int The time at which the job should timeout
     */
    public $timeout = 60;
    
    /**
     * @var bool Whether to delete the job if it times out
     */
    public $deleteWhenMissingModels = true;
    
    /**
     * Execute the job
     * 
     * @return void
     */
    abstract public function handle(): void;
    
    /**
     * Handle a job failure
     * 
     * @param \Exception $exception The exception that caused the failure
     * @return void
     */
    public function failed(\Exception $exception): void
    {
        // Default implementation does nothing
    }
    
    /**
     * Determine if the job should be retried
     * 
     * @param \Exception $exception The exception that caused the failure
     * @param int $attempts The number of attempts
     * @return bool Whether the job should be retried
     */
    public function shouldRetry(\Exception $exception, int $attempts): bool
    {
        return $attempts < $this->tries;
    }
    
    /**
     * Get the number of seconds to wait before retrying the job
     * 
     * @param int $attempts The number of attempts
     * @return int The number of seconds to wait
     */
    public function retryDelay(int $attempts): int
    {
        return $this->retryAfter;
    }
    
    /**
     * Get the job identifier
     * 
     * @return string The job identifier
     */
    public function getJobId(): string
    {
        return get_class($this) . '@' . spl_object_hash($this);
    }
    
    /**
     * Get the job display name
     * 
     * @return string The job display name
     */
    public function displayName(): string
    {
        return get_class($this);
    }
    
    /**
     * Get the job queue name
     * 
     * @return string The job queue name
     */
    public function queue(): string
    {
        return Config::getInstance()->get('queue.default', 'default');
    }
    
    /**
     * Get the job connection name
     * 
     * @return string The job connection name
     */
    public function connection(): string
    {
        return Config::getInstance()->get('queue.connection', 'default');
    }
} 