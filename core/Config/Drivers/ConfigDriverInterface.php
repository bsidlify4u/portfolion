<?php

namespace Portfolion\Config\Drivers;

interface ConfigDriverInterface {
    /**
     * Get the configuration value for a key
     */
    public function get(string $key): mixed;
    
    /**
     * Set a configuration value
     */
    public function set(string $key, mixed $value): void;
    
    /**
     * Check if a configuration key exists
     */
    public function has(string $key): bool;
    
    /**
     * Load a configuration section
     */
    public function load(string $section): array;
    
    /**
     * Save the configuration
     */
    public function save(): void;
}
