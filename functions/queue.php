<?php

use Portfolion\Queue\QueueManager;

if (!function_exists('dispatch')) {
    /**
     * Dispatch a job to the queue.
     *
     * @param mixed $job The job to dispatch
     * @return bool Whether the operation was successful
     */
    function dispatch($job)
    {
        return app()->make(QueueManager::class)->push($job);
    }
}

if (!function_exists('dispatch_later')) {
    /**
     * Dispatch a job to the queue after a delay.
     *
     * @param int $delay The delay in seconds
     * @param mixed $job The job to dispatch
     * @return bool Whether the operation was successful
     */
    function dispatch_later(int $delay, $job)
    {
        return app()->make(QueueManager::class)->later($job, $delay);
    }
}

if (!function_exists('dispatch_sync')) {
    /**
     * Dispatch a job immediately (sync).
     *
     * @param mixed $job The job to dispatch
     * @return mixed The job result
     */
    function dispatch_sync($job)
    {
        return $job->handle();
    }
}

if (!function_exists('queue_size')) {
    /**
     * Get the size of a queue.
     *
     * @param string|null $queue The queue name
     * @return int The queue size
     */
    function queue_size(?string $queue = null)
    {
        return app()->make(QueueManager::class)->size($queue);
    }
}

if (!function_exists('queue_clear')) {
    /**
     * Clear a queue.
     *
     * @param string|null $queue The queue name
     * @return bool Whether the operation was successful
     */
    function queue_clear(?string $queue = null)
    {
        return app()->make(QueueManager::class)->clear($queue);
    }
} 