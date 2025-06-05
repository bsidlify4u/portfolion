<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set up testing environment
putenv('APP_ENV=testing');

// Define minimal app function that doesn't require the full framework
if (!function_exists('app')) {
    function app($abstract = null) {
        static $container = null;
        
        if ($container === null) {
            $container = new \stdClass();
            $container->bindings = [];
        }
        
        if ($abstract === null) {
            return $container;
        }
        
        if (isset($container->bindings[$abstract])) {
            return $container->bindings[$abstract];
        }
        
        return null;
    }
}

// Allow setting mock bindings in the container
function bind($abstract, $concrete) {
    $container = app();
    $container->bindings[$abstract] = $concrete;
}

// Set default timezone
date_default_timezone_set('UTC');

// Additional test utilities
class TestUtils {
    /**
     * Create a temporary file with the given content
     */
    public static function createTempFile($content = '', $extension = 'php') {
        $file = sys_get_temp_dir() . '/portfolion_test_' . uniqid() . '.' . $extension;
        file_put_contents($file, $content);
        return $file;
    }
    
    /**
     * Delete a temporary file
     */
    public static function deleteTempFile($file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Additional bootstrap logic specific to certain components can be added here 