<?php

namespace Portfolion\Hash;

class Argon2iHasher implements Hasher
{
    /**
     * The default memory cost factor.
     *
     * @var int
     */
    protected int $memory = 1024;

    /**
     * The default time cost factor.
     *
     * @var int
     */
    protected int $time = 2;

    /**
     * The default threads factor.
     *
     * @var int
     */
    protected int $threads = 2;

    /**
     * Create a new argon2i hasher instance.
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->memory = $options['memory'] ?? $this->memory;
        $this->time = $options['time'] ?? $this->time;
        $this->threads = $options['threads'] ?? $this->threads;
    }

    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    public function make(string $value, array $options = []): string
    {
        $memory = $options['memory'] ?? $this->memory;
        $time = $options['time'] ?? $this->time;
        $threads = $options['threads'] ?? $this->threads;

        return password_hash($value, PASSWORD_ARGON2I, [
            'memory_cost' => $memory,
            'time_cost' => $time,
            'threads' => $threads,
        ]);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        $memory = $options['memory'] ?? $this->memory;
        $time = $options['time'] ?? $this->time;
        $threads = $options['threads'] ?? $this->threads;

        return password_needs_rehash($hashedValue, PASSWORD_ARGON2I, [
            'memory_cost' => $memory,
            'time_cost' => $time,
            'threads' => $threads,
        ]);
    }

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue
     * @return array
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Set the default password memory factor.
     *
     * @param int $memory
     * @return $this
     */
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Set the default password time factor.
     *
     * @param int $time
     * @return $this
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Set the default password threads factor.
     *
     * @param int $threads
     * @return $this
     */
    public function setThreads(int $threads): self
    {
        $this->threads = $threads;

        return $this;
    }
} 