<?php

namespace Portfolion\Hash;

class HashManager
{
    /**
     * The default driver name.
     *
     * @var string
     */
    protected string $defaultDriver = 'bcrypt';

    /**
     * The drivers array.
     *
     * @var array
     */
    protected array $drivers = [];

    /**
     * Create a new hash manager instance.
     */
    public function __construct()
    {
        $this->drivers = [
            'bcrypt' => new BcryptHasher(),
            'argon2i' => new Argon2iHasher(),
            'argon2id' => new Argon2idHasher(),
        ];
    }

    /**
     * Get a driver instance.
     *
     * @param string|null $driver
     * @return Hasher
     *
     * @throws \InvalidArgumentException
     */
    public function driver(?string $driver = null): Hasher
    {
        $driver = $driver ?: $this->defaultDriver;

        if (!isset($this->drivers[$driver])) {
            throw new \InvalidArgumentException("Driver [{$driver}] not supported.");
        }

        return $this->drivers[$driver];
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
        return $this->driver()->make($value, $options);
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
        return $this->driver()->check($value, $hashedValue, $options);
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
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue
     * @return array
     */
    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }

    /**
     * Set the default driver name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        $this->defaultDriver = $name;
    }

    /**
     * Register a custom driver.
     *
     * @param string $driver
     * @param Hasher $hasher
     * @return void
     */
    public function extend(string $driver, Hasher $hasher): void
    {
        $this->drivers[$driver] = $hasher;
    }
} 