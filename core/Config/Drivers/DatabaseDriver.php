<?php

namespace Portfolion\Config\Drivers;

use PDO;
use RuntimeException;

class DatabaseDriver implements ConfigDriverInterface {
    private PDO $db;
    private string $table;
    private array $cache = [];
    
    public function __construct(PDO $db, string $table = 'configurations') {
        $this->db = $db;
        $this->table = $table;
        $this->ensureTableExists();
    }
    
    private function ensureTableExists(): void {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            section VARCHAR(255) NOT NULL,
            config_key VARCHAR(255) NOT NULL,
            value TEXT,
            PRIMARY KEY (section, config_key)
        )";
        
        $this->db->exec($sql);
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
        
        // Update cache
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
        
        // Save to database
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (section, config_key, value) 
             VALUES (:section, :key, :value)
             ON DUPLICATE KEY UPDATE value = :value"
        );
        
        $stmt->execute([
            'section' => $section,
            'key' => $key,
            'value' => serialize($value)
        ]);
    }
    
    public function has(string $key): bool {
        return $this->get($key) !== null;
    }
    
    public function load(string $section): array {
        $stmt = $this->db->prepare(
            "SELECT config_key, value FROM {$this->table} WHERE section = ?"
        );
        
        $stmt->execute([$section]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $config = [];
        foreach ($rows as $row) {
            $keys = explode('.', $row['config_key']);
            array_shift($keys); // Remove section
            
            $ref = &$config;
            foreach ($keys as $i => $key) {
                if ($i === array_key_last($keys)) {
                    $ref[$key] = unserialize($row['value']);
                    break;
                }
                
                if (!isset($ref[$key])) {
                    $ref[$key] = [];
                }
                
                $ref = &$ref[$key];
            }
        }
        
        $this->cache[$section] = $config;
        return $config;
    }
    
    public function save(): void {
        foreach ($this->cache as $section => $config) {
            $this->flattenAndSave($section, $config);
        }
    }
    
    private function flattenAndSave(string $section, array $config, string $prefix = ''): void {
        foreach ($config as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $this->flattenAndSave($section, $value, $fullKey);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO {$this->table} (section, config_key, value) 
                     VALUES (:section, :key, :value)
                     ON DUPLICATE KEY UPDATE value = :value"
                );
                
                $stmt->execute([
                    'section' => $section,
                    'key' => "{$section}.{$fullKey}",
                    'value' => serialize($value)
                ]);
            }
        }
    }
}
