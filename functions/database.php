<?php
/**
 * Database Configuration File
 * 
 * SECURITY NOTE: In production, this file should:
 * 1. Be stored outside of web root
 * 2. Have restricted file permissions (chmod 600)
 * 3. Use environment variables instead of hardcoded values
 */

// Attempt to load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'portfolion');
define('DB_USER', getenv('DB_USER') ?: 'portfolion');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'portfolion');

// Additional security configurations
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// SSL/TLS Configuration (recommended for production)
define('DB_SSL', true);
define('DB_SSL_KEY', getenv('DB_SSL_KEY') ?: null);
define('DB_SSL_CERT', getenv('DB_SSL_CERT') ?: null);
define('DB_SSL_CA', getenv('DB_SSL_CA') ?: null);

// Connection timeout settings
define('DB_TIMEOUT', 30); // 30 seconds
define('DB_CONNECT_TIMEOUT', 15); // 15 seconds

// Maximum number of connections in the pool
define('DB_MAX_CONNECTIONS', 100);

// Error reporting configuration
ini_set('display_errors', 0); // Disable error display in production
error_reporting(E_ALL); // Log all errors

// Verify that critical constants are defined
if (!defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASSWORD')) {
    error_log('Critical database configuration missing');
    die('Database configuration error');
}
