<?php
namespace Portfolion\Queue;

interface QueueInterface {
    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int;

    /**
     * Push a new job onto the queue.
     *
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(object|string $job, mixed $data = '', ?string $queue = null): mixed;

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(\DateTimeInterface|\DateInterval|int $delay, object|string $job, mixed $data = '', ?string $queue = null): mixed;

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = []): mixed;

    /**
     * Push a raw payload onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function laterRaw(\DateTimeInterface|\DateInterval|int $delay, string $payload, ?string $queue = null, array $options = []): mixed;

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): mixed;

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return \Portfolion\Queue\Jobs\JobInterface|null
     */
    public function pop(?string $queue = null): ?Jobs\JobInterface;

    /**
     * Get the connection name.
     *
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Set the connection name.
     *
     * @param string $name
     * @return $this
     */
    public function setConnectionName(string $name): self;
}
