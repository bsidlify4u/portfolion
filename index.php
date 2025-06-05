<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if composer autoload exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Please run "composer install" to install dependencies');
}

// Load core files
require_once __DIR__ . '/vendor/autoload.php';

// Load helper functions
require_once __DIR__ . '/functions/helpers.php';

// Start a session
session_start();

// Load routes
require __DIR__ . '/routes/web.php';

// Create a request object
$request = new \Portfolion\Http\Request();

// Get the router instance
$router = \Portfolion\Routing\Router::getInstance();

// Dispatch the request
$response = $router->dispatch($request);

// Send response to client
echo $response->getContent();
