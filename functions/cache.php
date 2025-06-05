<?php

use Portfolion\Cache\Cache;

if (!function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     *
     * @param  string|array  $key
     * @param  mixed  $default
     * @return mixed|\Portfolion\Cache\Cache
     *
     * @throws \Exception
     */
    function cache($key = null, $default = null)
    {
        $cache = Cache::getInstance();

        if (is_null($key)) {
            return $cache;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $cache->put($k, $v);
            }
            return null;
        }

        return $cache->get($key, $default);
    }
}

if (!function_exists('cache_has')) {
    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return bool
     */
    function cache_has($key)
    {
        return Cache::getInstance()->has($key);
    }
}

if (!function_exists('cache_get')) {
    /**
     * Get an item from the cache.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function cache_get($key, $default = null)
    {
        return Cache::getInstance()->get($key, $default);
    }
}

if (!function_exists('cache_put')) {
    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|null  $ttl
     * @return bool
     */
    function cache_put($key, $value, $ttl = null)
    {
        return Cache::getInstance()->put($key, $value, $ttl);
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $key
     * @param  int|null  $ttl
     * @param  \Closure  $callback
     * @return mixed
     */
    function cache_remember($key, $ttl, $callback)
    {
        return Cache::getInstance()->remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    function cache_forget($key)
    {
        return Cache::getInstance()->forget($key);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    function cache_flush()
    {
        return Cache::getInstance()->flush();
    }
}

if (!function_exists('cache_forever')) {
    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    function cache_forever($key, $value)
    {
        return Cache::getInstance()->forever($key, $value);
    }
}

if (!function_exists('cache_tags')) {
    /**
     * Begin a cache tags operation.
     *
     * @param  array|string  $tags
     * @return \Portfolion\Cache\Store\TaggedCache
     */
    function cache_tags($tags)
    {
        return Cache::getInstance()->tags($tags);
    }
} 