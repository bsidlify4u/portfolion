<?php

namespace Portfolion\Config\Drivers;

use Redis;
use RuntimeException;

class RedisDriver implements ConfigDriverInterface {
    private Redis $redis;
    private string $prefix;
    private array $cache = [];
    
    public function __construct(Redis $redis, string $prefix = 'config:') {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }
    
    public function get(string $key): mixed {
        $keys = explode('.', $key);
        $section = $keys[0];
        
        // Load section if not in cache
        if (!isset($this->cache[$section])) {
            $this->load($section);
        }
        
        $config = $this->cache;
        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return null;
            }
            $config = $config[$segment];
        }
        
        return $config;
    }
    
    public function set(string $key, mixed $value): void {
        $keys = explode('.', $key);
        $section = $keys[0];
        
        // Load section if not in cache
        if (!isset($this->cache[$section])) {
            $this->load($section);
        }
        
        $config = &$this->cache;
        foreach ($keys as $i => $segment) {
            if ($i === array_key_last($keys)) {
                $config[$segment] = $value;
                break;
            }
            
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            
            $config = &$config[$segment];
        }
        
        // Save section
        $this->redis->set(
            $this->prefix . $section,
            serialize($this->cache[$section])
        );
    }
    
    public function has(string $key): bool {
        return $this->get($key) !== null;
    }
    
    public function load(string $section): array {
        $data = $this->redis->get($this->prefix . $section);
        if ($data === false) {
            $this->cache[$section] = [];
            return [];
        }
        
        $config = unserialize($data);
        if (!is_array($config)) {
            throw new RuntimeException("Invalid configuration data in Redis for section: {$section}");
        }
        
        $this->cache[$section] = $config;
        return $config;
    }
    
    public function save(): void {
        foreach ($this->cache as $section => $config) {
            $this->redis->set(
                $this->prefix . $section,
                serialize($config)
            );
        }
    }
}
