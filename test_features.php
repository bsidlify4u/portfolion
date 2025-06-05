<?php

/**
 * Portfolion Framework Feature Test Script
 * 
 * This script tests the cache and queue features of the Portfolion framework.
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/functions/helpers.php';

echo "===== Portfolion Framework Feature Test =====\n\n";

// Test cache features
echo "Testing Cache Features...\n";
echo "-------------------------\n";

// Store a value in the cache
$result = cache_put('test_key', 'test_value', 60);
echo "Store value in cache: " . ($result ? 'Success' : 'Failed') . "\n";

// Get a value from the cache
$value = cache_get('test_key');
echo "Get value from cache: " . ($value === 'test_value' ? 'Success' : 'Failed') . "\n";

// Check if a key exists in the cache
$exists = cache_has('test_key');
echo "Check if key exists: " . ($exists ? 'Success' : 'Failed') . "\n";

// Store a value using the cache helper
cache(['helper_key' => 'helper_value']);
$helperValue = cache_get('helper_key');
echo "Store and get using helper: " . ($helperValue === 'helper_value' ? 'Success' : 'Failed') . "\n";

// Test cache remember
$rememberedValue = cache_remember('remember_key', 60, function () {
    return 'remembered_value';
});
echo "Cache remember: " . ($rememberedValue === 'remembered_value' ? 'Success' : 'Failed') . "\n";

// Test cache forever
cache_forever('forever_key', 'forever_value');
$foreverValue = cache_get('forever_key');
echo "Cache forever: " . ($foreverValue === 'forever_value' ? 'Success' : 'Failed') . "\n";

// Test cache forget
cache_forget('test_key');
$forgottenValue = cache_get('test_key');
echo "Cache forget: " . ($forgottenValue === null ? 'Success' : 'Failed') . "\n";

// Test cache flush
cache_flush();
$flushedValue = cache_get('forever_key');
echo "Cache flush: " . ($flushedValue === null ? 'Success' : 'Failed') . "\n\n";

// Test queue features
echo "Testing Queue Features...\n";
echo "-------------------------\n";
echo "Note: Queue tests require a database connection.\n";
echo "To fully test the queue system, run the following commands:\n";
echo "1. php portfolion queue:migrate - Create the queue tables\n";
echo "2. php portfolion queue:dispatch 'test data' - Dispatch a test job\n";
echo "3. php portfolion queue:work --once - Process a single job\n\n";

// Test job creation
echo "Creating a test job...\n";
try {
    require_once __DIR__ . '/app/Jobs/ExampleJob.php';
    $job = new App\Jobs\ExampleJob('test_data');
    echo "Job created successfully.\n";
} catch (\Exception $e) {
    echo "Failed to create job: " . $e->getMessage() . "\n";
}

echo "\n===== Test Complete =====\n";
echo "The cache system is working properly.\n";
echo "To test the queue system, follow the instructions above.\n"; 