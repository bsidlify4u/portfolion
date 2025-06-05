<?php

namespace Portfolion\Config\Drivers;

use RuntimeException;

class FileDriver implements ConfigDriverInterface {
    private array $config = [];
    private string $configPath;
    
    public function __construct(string $configPath) {
        $this->configPath = $configPath;
    }
    
    public function get(string $key): mixed {
        $keys = explode('.', $key);
        $config = $this->config;
        
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
        $config = &$this->config;
        
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
    }
    
    public function has(string $key): bool {
        $keys = explode('.', $key);
        $config = $this->config;
        
        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return false;
            }
            $config = $config[$segment];
        }
        
        return true;
    }
    
    public function load(string $section): array {
        $file = $this->configPath . DIRECTORY_SEPARATOR . $section . '.php';
        
        if (!file_exists($file)) {
            throw new RuntimeException("Configuration file not found: {$file}");
        }
        
        $config = require $file;
        if (!is_array($config)) {
            throw new RuntimeException("Configuration file must return an array: {$file}");
        }
        
        $this->config[$section] = $config;
        return $config;
    }
    
    public function save(): void {
        foreach ($this->config as $section => $config) {
            $file = $this->configPath . DIRECTORY_SEPARATOR . $section . '.php';
            $content = '<?php return ' . var_export($config, true) . ';';
            file_put_contents($file, $content);
        }
    }
}
