<?php

/**
 * Test environment configuration
 * 
 * This file provides environment settings for the testing environment.
 * Used by bootstrap.php to set up the proper test configuration.
 */

return [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'true',
    'APP_KEY' => 'base64:testingtestingtestingtestingtesting==',
    'APP_URL' => 'http://localhost',

    // Use in-memory SQLite for testing by default
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',

    // Cache and session configuration for testing
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array',

    // Ensure testing config is used
    'USE_TEST_CONFIG' => 'true',

    // Log settings
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'debug'
]; 