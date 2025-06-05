<?php

/**
 * Portfolion - Development Server Router
 *
 * This file is used by the PHP development server to route all requests
 * to the application's front controller. This allows the development server
 * to mimic the behavior of a production server with URL rewriting.
 */

// Define the absolute path to the project's public directory
$publicPath = __DIR__ . DIRECTORY_SEPARATOR . 'public';

// Ensure the public directory exists
if (!is_dir($publicPath)) {
    echo "Error: Public directory does not exist at {$publicPath}\n";
    exit(1);
}

// Get the URI from the request
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle static files
$staticFilePath = $publicPath . $uri;
if ($uri !== '/' && file_exists($staticFilePath) && !is_dir($staticFilePath)) {
    // If it's a PHP file, include it instead of returning false
    if (substr($staticFilePath, -4) === '.php') {
        require_once $staticFilePath;
        return true;
    }
    
    // For other static files, let the built-in server handle it
    return false;
}

// For all other requests, include the front controller
$indexPath = $publicPath . DIRECTORY_SEPARATOR . 'index.php';
if (!file_exists($indexPath)) {
    echo "Error: Front controller not found at {$indexPath}\n";
    exit(1);
}

require_once $indexPath;