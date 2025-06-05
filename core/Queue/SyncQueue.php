<?php

namespace Portfolion\Queue;

use Portfolion\Queue\QueueInterface;
use Portfolion\Contracts\Queue\ShouldQueue;

/**
 * SyncQueue immediately executes jobs synchronously without queueing them
 */
class SyncQueue extends Queue implements QueueInterface
{
    /**
     * The name of the connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->resolveJob($job, $data);
        
        try {
            $queueJob->handle();
        } catch (\Exception $e) {
            $this->handleException($queueJob, $e);
        }
        
        return 0;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        // Since it's sync, we don't need to push raw payloads
        return 0;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a raw payload onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function laterRaw($delay, $payload, $queue = null, array $options = [])
    {
        return $this->push($payload, '', $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Portfolion\Queue\Jobs\JobInterface|null
     */
    public function pop($queue = null)
    {
        return null;
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @return object
     */
    protected function resolveJob($job, $data)
    {
        if (is_object($job) && $job instanceof ShouldQueue) {
            return $job;
        }

        if (is_string($job)) {
            return new $job($data);
        }

        throw new \InvalidArgumentException('Invalid job provided to SyncQueue');
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * @param  object  $queueJob
     * @param  \Exception  $e
     * @return void
     */
    protected function handleException($queueJob, \Exception $e)
    {
        if (method_exists($queueJob, 'failed')) {
            $queueJob->failed($e);
        }

        throw $e;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Delete a job from the queue
     * 
     * @param mixed $job The job to delete
     * @return bool Success indicator
     */
    public function delete($job): bool
    {
        return true; // Nothing to delete
    }
    
    /**
     * Release a job back onto the queue
     * 
     * @param mixed $job The job to release
     * @param int $delay The delay in seconds
     * @return bool Success indicator
     */
    public function release($job, int $delay = 0): bool
    {
        return true; // Nothing to release
    }
    
    /**
     * Push multiple jobs onto the queue
     * 
     * @param array $jobs The jobs to push
     * @return bool Success indicator
     */
    public function bulk(array $jobs): bool
    {
        foreach ($jobs as $job) {
            $this->push($job);
        }
        
        return true;
    }
    
    /**
     * Clear the queue
     * 
     * @param string $queue The queue to clear
     * @return bool Success indicator
     */
    public function clear(string $queue): bool
    {
        return true; // Nothing to clear
    }
} 