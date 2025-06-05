<?php

/**
 * Portfolion PHP Framework - Task Management Application
 * 
 * This file is the front controller for the application.
 * All requests are routed through this file.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the application start time
define('PORTFOLION_START', microtime(true));

// Check if we're running in maintenance mode
$maintenance = __DIR__.'/../storage/framework/maintenance.php';
if (file_exists($maintenance)) {
    require $maintenance;
}

// Register the auto loader
require __DIR__.'/../vendor/autoload.php';

// Load helper functions
require __DIR__.'/../functions/helpers.php';

// Start the session
session_start();

// Bootstrap application services
require __DIR__.'/../app/bootstrap.php';

// Load the routes
require __DIR__.'/../routes/web.php';

// Create a request from the server variables
$request = new \Portfolion\Http\Request();

// Get the router instance
$router = \Portfolion\Routing\Router::getInstance();

// Dispatch the request through the router
$response = $router->dispatch($request);

// Send the response back to the client
echo $response->getContent();
