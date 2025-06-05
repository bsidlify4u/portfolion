<?php
namespace Portfolion\Cache;

interface StoreInterface {
    public function get(string $key);
    public function put(string $key, $value, int $ttl = 3600): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function forever(string $key, $value): bool;
    public function increment(string $key, int $value = 1): int;
    public function decrement(string $key, int $value = 1): int;
    public function clear(): bool;
}
