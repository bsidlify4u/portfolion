<?php

/**
 * This script clears the configuration cache files to fix serialization issues.
 * Run this script before running tests if you encounter Closure serialization errors.
 */

$projectRoot = __DIR__;
$configCacheFile = $projectRoot . '/storage/framework/cache/config.cache.php';
$schemaCache = $projectRoot . '/storage/framework/cache/config.schema.php';

// Clear config cache file
if (file_exists($configCacheFile)) {
    echo "Removing config cache file: $configCacheFile\n";
    unlink($configCacheFile);
} else {
    echo "Config cache file not found.\n";
}

// Clear schema cache file
if (file_exists($schemaCache)) {
    echo "Removing schema cache file: $schemaCache\n";
    unlink($schemaCache);
} else {
    echo "Schema cache file not found.\n";
}

echo "Configuration cache cleared successfully.\n"; 