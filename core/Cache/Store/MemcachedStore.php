<?php
namespace Portfolion\Cache\Store;

use Memcached;
use RuntimeException;

class MemcachedStore implements StoreInterface {
    private Memcached $memcached;
    private array $config;
    
    /**
     * @param array{servers: array<array{host: string, port: int, weight?: int}>, persistent_id?: string|null} $config
     * @throws RuntimeException
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect(): void {
        $persistentId = $this->config['persistent_id'] ?? null;
        $this->memcached = new Memcached($persistentId);
        
        if (empty($this->memcached->getServerList())) {
            $this->memcached->addServers(array_map(function($server) {
                return [
                    $server['host'],
                    $server['port'],
                    $server['weight'] ?? 0
                ];
            }, $this->config['servers']));
        }
        
        if ($this->memcached->getVersion() === false) {
            throw new RuntimeException('Failed to connect to Memcached server');
        }
    }
    
    public function get(string $key, mixed $default = null): mixed {
        $value = $this->memcached->get($key);
        
        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return $default;
        }
        
        return $value;
    }
    
    public function put(string $key, mixed $value, int $ttl = 0): bool {
        return $this->memcached->set($key, $value, $ttl);
    }
    
    public function forget(string $key): bool {
        return $this->memcached->delete($key);
    }
    
    public function flush(): bool {
        return $this->memcached->flush();
    }
    
    public function increment(string $key, int $value = 1): int|false {
        $newValue = $this->memcached->increment($key, $value);
        
        if ($newValue === false && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            $this->put($key, $value);
            return $value;
        }
        
        return $newValue;
    }
    
    public function decrement(string $key, int $value = 1): int|false {
        $newValue = $this->memcached->decrement($key, $value);
        
        if ($newValue === false && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            $this->put($key, 0);
            return 0;
        }
        
        return $newValue;
    }
    
    public function forever(string $key, mixed $value): bool {
        return $this->put($key, $value, 0);
    }
}
