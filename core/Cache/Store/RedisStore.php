<?php
namespace Portfolion\Cache\Store;

use Redis;
use RuntimeException;

class RedisStore implements StoreInterface {
    private Redis $redis;
    private array $config;
    
    /**
     * @param array{host: string, port: int, password?: string|null, database?: int} $config
     * @throws RuntimeException
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect(): void {
        $this->redis = new Redis();
        
        if (!$this->redis->connect(
            $this->config['host'],
            $this->config['port']
        )) {
            throw new RuntimeException('Failed to connect to Redis server');
        }
        
        if (isset($this->config['password'])) {
            $this->redis->auth($this->config['password']);
        }
        
        if (isset($this->config['database'])) {
            $this->redis->select($this->config['database']);
        }
    }
    
    public function get(string $key, mixed $default = null): mixed {
        $value = $this->redis->get($key);
        
        if ($value === false) {
            return $default;
        }
        
        $decoded = json_decode($value, true);
        return $decoded === null ? $value : $decoded;
    }
    
    public function put(string $key, mixed $value, int $ttl = 0): bool {
        $value = is_numeric($value) || is_string($value) ? $value : json_encode($value);
        
        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $value);
        }
        
        return $this->redis->set($key, $value);
    }
    
    public function forget(string $key): bool {
        return $this->redis->del($key) > 0;
    }
    
    public function flush(): bool {
        return $this->redis->flushDB();
    }
    
    public function increment(string $key, int $value = 1): int|false {
        return $this->redis->incrBy($key, $value);
    }
    
    public function decrement(string $key, int $value = 1): int|false {
        return $this->redis->decrBy($key, $value);
    }
    
    public function forever(string $key, mixed $value): bool {
        return $this->put($key, $value);
    }
}
