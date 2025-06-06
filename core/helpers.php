<?php

/**
 * Helper Functions
 * 
 * This file contains helper functions used throughout the application.
 */

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }
        
        return $value;
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '') {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param  string  $path
     * @return string
     */
    function app_path($path = '') {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the path to the config folder.
     *
     * @param  string  $path
     * @return string
     */
    function config_path($path = '') {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '') {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '') {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param  string  $path
     * @return string
     */
    function resource_path($path = '') {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the path to the database folder.
     *
     * @param  string  $path
     * @return string
     */
    function database_path($path = '') {
        return base_path('database' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
} 