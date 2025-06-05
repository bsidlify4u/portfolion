<?php

namespace Portfolion\Queue;

use Portfolion\Contracts\Queue\ShouldQueue;

class PayloadCreator
{
    /**
     * Create a payload for a job.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return array
     */
    public function create($job, $data = '', $queue = null)
    {
        if ($job instanceof ShouldQueue) {
            return $this->createObjectPayload($job);
        }

        return $this->createStringPayload($job, $data);
    }

    /**
     * Create a payload for an object-based queue job.
     *
     * @param  object  $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        $payload = [
            'job' => serialize($job),
            'attempts' => 0,
            'id' => $this->getRandomId(),
            'created_at' => $this->getTimestamp(),
        ];

        // We would dispatch an event here in a full implementation
        // Event::dispatch('queue.creating', [$payload]);

        return $payload;
    }

    /**
     * Create a payload for a string-based queue job.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @return array
     */
    protected function createStringPayload($job, $data)
    {
        $payload = [
            'job' => $job,
            'data' => serialize($data),
            'attempts' => 0,
            'id' => $this->getRandomId(),
            'created_at' => $this->getTimestamp(),
        ];

        // We would dispatch an event here in a full implementation
        // Event::dispatch('queue.creating', [$payload]);

        return $payload;
    }

    /**
     * Get a random ID for the job.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return md5(uniqid('', true));
    }

    /**
     * Get the current timestamp for the job.
     *
     * @return int
     */
    protected function getTimestamp()
    {
        return time();
    }
} 