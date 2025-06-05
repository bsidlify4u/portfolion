<?php

namespace Portfolion\Queue;

use DateInterval;
use DateTimeInterface;
use Portfolion\Queue\Jobs\JobInterface;
use Portfolion\Support\Str;

abstract class Queue implements QueueInterface
{
    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected string $connectionName;

    /**
     * Default queue name.
     *
     * @var string
     */
    protected string $default = 'default';

    /**
     * Push a new job onto the queue.
     *
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(object|string $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(DateTimeInterface|DateInterval|int $delay, object|string $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $this->secondsUntil($delay));
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = []): mixed
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a raw payload onto the queue after a delay.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function laterRaw(DateTimeInterface|DateInterval|int $delay, string $payload, ?string $queue = null, array $options = []): mixed
    {
        return $this->pushToDatabase($queue, $payload, $this->secondsUntil($delay));
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): mixed
    {
        $payloads = [];

        foreach ($jobs as $job) {
            $payloads[] = $this->createPayload($job, $data);
        }

        return $this->pushBatchToDatabase($queue, $payloads);
    }

    /**
     * Create a payload for the job.
     *
     * @param object|string $job
     * @param mixed $data
     * @return string
     */
    protected function createPayload(object|string $job, mixed $data = ''): string
    {
        if (is_object($job)) {
            $payload = json_encode([
                'job' => get_class($job),
                'data' => $this->prepareData($job),
                'id' => Str::uuid(),
                'attempts' => 0,
            ]);
        } else {
            $payload = json_encode([
                'job' => $job,
                'data' => $data,
                'id' => Str::uuid(),
                'attempts' => 0,
            ]);
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                'Unable to create payload: ' . json_last_error_msg()
            );
        }

        return $payload;
    }

    /**
     * Prepare the data for a job.
     *
     * @param object $job
     * @return array
     */
    protected function prepareData(object $job): array
    {
        $data = [];

        // If the job has a prepare method, we'll call it and use the result as data
        if (method_exists($job, 'prepare')) {
            $data = $job->prepare();
        } else {
            // Otherwise, we'll use reflection to get all the public properties
            $reflect = new \ReflectionClass($job);
            $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $data[$property->getName()] = $property->getValue($job);
            }
        }

        return $data;
    }

    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     * @return int
     */
    protected function secondsUntil(DateTimeInterface|DateInterval|int $delay): int
    {
        if ($delay instanceof DateTimeInterface) {
            return max(0, $delay->getTimestamp() - time());
        }

        if ($delay instanceof DateInterval) {
            return max(0, (new \DateTime())->add($delay)->getTimestamp() - time());
        }

        return max(0, $delay);
    }

    /**
     * Push to the database.
     *
     * @param string|null $queue
     * @param string $payload
     * @param int $delay
     * @return mixed
     */
    abstract protected function pushToDatabase(?string $queue, string $payload, int $delay = 0): mixed;

    /**
     * Push multiple jobs to the database.
     *
     * @param string|null $queue
     * @param array $payloads
     * @return mixed
     */
    abstract protected function pushBatchToDatabase(?string $queue, array $payloads): mixed;

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     * @return string
     */
    protected function getQueue(?string $queue): string
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param string $name
     * @return $this
     */
    public function setConnectionName(string $name): self
    {
        $this->connectionName = $name;

        return $this;
    }
} 