<?php

namespace Portfolion\Queue\Jobs;

abstract class Job implements JobInterface
{
    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected mixed $instance;

    /**
     * The IoC container instance.
     *
     * @var \Portfolion\Foundation\Application
     */
    protected $container;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected bool $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected bool $released = false;

    /**
     * Create a new job instance.
     *
     * @param \Portfolion\Foundation\Application $container
     * @return void
     */
    public function __construct($container = null)
    {
        $this->container = $container ?: app();
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire(): void
    {
        $payload = $this->payload();

        list($class, $method) = $this->parseJob($payload['job']);

        $this->instance = $this->resolve($class);

        $this->instance->{$method}($this, $payload['data'] ?? []);
    }

    /**
     * Parse the job class and method.
     *
     * @param string $job
     * @return array
     */
    protected function parseJob(string $job): array
    {
        // If the job is a class@method string, we'll parse it and get the class and method
        if (strpos($job, '@') !== false) {
            return explode('@', $job);
        }

        // Otherwise, we'll assume the job is a simple class name and call the 'handle' method
        return [$job, 'handle'];
    }

    /**
     * Resolve the given class name from the container.
     *
     * @param string $class
     * @return object
     */
    protected function resolve(string $class): object
    {
        // If the container has the class registered, we'll use it
        if ($this->container->has($class)) {
            return $this->container->make($class);
        }

        // Otherwise, we'll create a new instance
        return new $class;
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        return json_decode($this->getRawBody(), true);
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Determine if the job has been released.
     *
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * Get the name of the queued job.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->payload()['job'];
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * @return string
     */
    public function getResolvedName(): string
    {
        $name = $this->payload()['job'];

        if (strpos($name, '@') !== false) {
            return explode('@', $name)[0];
        }

        return $name;
    }

    /**
     * Get the UUID of the job.
     *
     * @return string|null
     */
    public function getJobId(): ?string
    {
        return $this->payload()['id'] ?? null;
    }

    /**
     * Get the container instance.
     *
     * @return \Portfolion\Foundation\Application
     */
    public function getContainer()
    {
        return $this->container;
    }
} 