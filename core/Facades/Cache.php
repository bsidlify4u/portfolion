<?php

namespace Portfolion\Facades;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool put(string $key, mixed $value, int|null $ttl = null)
 * @method static bool forever(string $key, mixed $value)
 * @method static bool forget(string $key)
 * @method static bool flush()
 * @method static bool has(string $key)
 * @method static int|bool increment(string $key, int $value = 1)
 * @method static int|bool decrement(string $key, int $value = 1)
 * @method static mixed remember(string $key, int|null $ttl, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static int|null ttl(string $key)
 * @method static \Portfolion\Cache\Drivers\DriverInterface store(string|null $name = null)
 * 
 * @see \Portfolion\Cache\Cache
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cache';
    }
} 