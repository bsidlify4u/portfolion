<?php

if (!function_exists('base_path')) {
    /**
     * Get the base path of the application.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $rootPath = realpath(__DIR__ . '/..');
        return $rootPath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path of the application.
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage/' . ($path ? ltrim($path, '/') : ''));
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the database path of the application.
     *
     * @param string $path
     * @return string
     */
    function database_path(string $path = ''): string
    {
        return base_path('database/' . ($path ? ltrim($path, '/') : ''));
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class basename of the given object / class.
     *
     * @param string|object $class
     * @return string
     */
    function class_basename($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        static $envValues = null;
        
        if ($envValues === null) {
            $envValues = [];
            
            // Load .env file if it exists
            $envFile = base_path('.env');
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Skip comments
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    
                    // Parse KEY=value format
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        
                        // Remove quotes if present
                        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                            $value = substr($value, 1, -1);
                        } else if (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                            $value = substr($value, 1, -1);
                        }
                        
                        $envValues[$name] = $value;
                    }
                }
            }
        }
        
        // Check environment variables from .env file
        if (isset($envValues[$key])) {
            $value = $envValues[$key];
        } else {
            // Fall back to getenv() for system environment variables
            $value = getenv($key);
            
            if ($value === false) {
                return $default;
            }
        }
        
        // Convert special values
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        return $value;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a given URL.
     *
     * @param string $url
     * @return void
     */
    function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date.
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    function format_date(?string $date, string $format = 'Y-m-d'): string
    {
        if (!$date) {
            return 'N/A';
        }
        
        return date($format, strtotime($date));
    }
}

if (!function_exists('config_path')) {
    function config_path($path = ''): string {
        return base_path('config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('app_path')) {
    function app_path($path = ''): string {
        return base_path('app') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('resource_path')) {
    function resource_path($path = ''): string {
        return base_path('resources') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('public_path')) {
    function public_path($path = ''): string {
        return base_path('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('response')) {
    function response($content = '', int $status = 200, array $headers = []): \Portfolion\Http\Response {
        return new \Portfolion\Http\Response($content, $status, $headers);
    }
}

if (!function_exists('view')) {
    /**
     * Return a view response.
     *
     * @param string $view
     * @param array $data
     * @return \Portfolion\Http\Response
     */
    function view(string $view, array $data = []): \Portfolion\Http\Response {
        $response = new \Portfolion\Http\Response();
        return $response->view($view, $data);
    }
}

if (!function_exists('logger')) {
    /**
     * Get a logger instance
     * 
     * @return \Portfolion\Logging\Logger
     */
    function logger(): \Portfolion\Logging\Logger {
        return \Portfolion\Logging\Logger::getInstance();
    }
}

// Include session helper functions
require_once __DIR__ . '/session.php';

// Include config helper functions
require_once __DIR__ . '/config.php';

// Include cache helper functions
require_once __DIR__ . '/cache.php';

// Include queue helper functions
require_once __DIR__ . '/queue.php';

// Include app helper functions
require_once __DIR__ . '/app.php';
