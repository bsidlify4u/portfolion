<?php

namespace Portfolion\Queue\Jobs;

interface JobInterface
{
    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire(): void;

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     * @return void
     */
    public function release(int $delay = 0): void;

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void;

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * Determine if the job has been released.
     *
     * @return bool
     */
    public function isReleased(): bool;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int;

    /**
     * Get the name of the queued job.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the resolved name of the queued job class.
     *
     * @return string
     */
    public function getResolvedName(): string;

    /**
     * Get the UUID of the job.
     *
     * @return string|null
     */
    public function getJobId(): ?string;

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody(): string;

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string|null
     */
    public function getConnectionName(): ?string;

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): string;
} 