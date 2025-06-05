<?php

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  string|array|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        static $configs = [];
        
        // If no key provided, return all configs
        if (is_null($key)) {
            return $configs;
        }
        
        // If key is an array, set multiple config values
        if (is_array($key)) {
            foreach ($key as $innerKey => $value) {
                config($innerKey, $value);
            }
            return null;
        }
        
        // Parse the key to get the file and item
        $parts = explode('.', $key);
        $file = $parts[0];
        
        // If the config array doesn't have this file, load it
        if (!isset($configs[$file])) {
            $configPath = config_path("{$file}.php");
            if (file_exists($configPath)) {
                $configs[$file] = require $configPath;
            } else {
                $configs[$file] = [];
            }
        }
        
        // Return the whole config file if only the file is specified
        if (count($parts) === 1) {
            return $configs[$file] ?? $default;
        }
        
        // Traverse the config array to get the value
        $current = $configs[$file];
        
        for ($i = 1; $i < count($parts); $i++) {
            $part = $parts[$i];
            
            if (!is_array($current) || !isset($current[$part])) {
                return $default;
            }
            
            $current = $current[$part];
        }
        
        return $current;
    }
} 