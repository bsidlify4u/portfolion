<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP >= 7.4.0 required. Current version: ' . PHP_VERSION);
}

// Check required extensions
$required_extensions = [
    'pdo',
    'json',
    'mbstring',
    'openssl',
    'xml'
];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("Required PHP extension missing: {$ext}");
    }
}

// Create necessary directories
$directories = [
    'storage/cache',
    'storage/logs',
    'storage/framework',
    'storage/framework/views',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/uploads'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

// Initialize autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment configuration
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Enable error reporting for development
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Load environment configuration
$config = require __DIR__ . '/config/app.php';

// Initialize core components
use Core\Bootstrap;
use Core\Security\SecurityServiceProvider;

// Start the application
$app = Bootstrap::getInstance();

// Initialize security features
SecurityServiceProvider::init();

echo "Framework initialized successfully!\n";
