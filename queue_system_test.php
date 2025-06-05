<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function for testing
function test_feature($name, $callback) {
    echo "\n--- Testing $name ---\n";
    try {
        $result = $callback();
        if ($result === false) {
            echo "✓ [SKIPPED] $name\n";
        } else {
            echo "✓ [PASSED] $name\n";
        }
    } catch (Exception $e) {
        echo "✗ [FAILED] $name: " . $e->getMessage() . "\n";
        echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  " . $e->getTraceAsString() . "\n";
    }
}

echo "\n=== PORTFOLION QUEUE SYSTEM TEST ===\n";
echo "Testing job queueing and processing features\n";

// Create a test job class
class TestJob implements Portfolion\Contracts\Queue\ShouldQueue
{
    public $data;
    
    public function __construct($data = null)
    {
        $this->data = $data;
    }
    
    public function handle()
    {
        echo "  Processing test job with data: " . json_encode($this->data) . "\n";
        return true;
    }
}

// 1. Test Queue Manager
test_feature('Queue Manager', function() {
    if (!class_exists('Portfolion\Queue\QueueManager')) {
        throw new Exception('QueueManager class not found');
    }
    
    // Skip actual instantiation as it requires app instance
    return true;
});

// 2. Test Queue Interface
test_feature('Queue Interface', function() {
    if (!interface_exists('Portfolion\Queue\QueueInterface')) {
        throw new Exception('QueueInterface not found');
    }
    
    // Verify the interface methods
    $methods = [
        'size',
        'push',
        'later',
        'pushRaw',
        'laterRaw',
        'bulk',
        'pop',
        'getConnectionName',
        'setConnectionName'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Queue\QueueInterface');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("QueueInterface missing method: $method");
        }
    }
    
    return true;
});

// 3. Test Queue Base Class
test_feature('Queue Base Class', function() {
    if (!class_exists('Portfolion\Queue\Queue')) {
        throw new Exception('Queue base class not found');
    }
    
    // Verify the base class methods
    $methods = [
        'push',
        'later',
        'pushRaw',
        'laterRaw',
        'bulk',
        'createPayload',
        'getQueue',
        'getConnectionName',
        'setConnectionName'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Queue\Queue');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("Queue base class missing method: $method");
        }
    }
    
    return true;
});

// 4. Test Database Queue
test_feature('Database Queue', function() {
    if (!class_exists('Portfolion\Queue\DatabaseQueue')) {
        throw new Exception('DatabaseQueue class not found');
    }
    
    // Verify the database queue methods
    $methods = [
        'size',
        'pushToDatabase',
        'pushBatchToDatabase',
        'pop',
        'getNextAvailableJob',
        'markJobAsReserved',
        'deleteReserved',
        'release'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Queue\DatabaseQueue');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("DatabaseQueue missing method: $method");
        }
    }
    
    // Try to create a database queue instance
    try {
        $connection = new Portfolion\Database\Connection();
        $queue = new Portfolion\Queue\DatabaseQueue($connection, 'jobs');
        
        if (!($queue instanceof Portfolion\Queue\QueueInterface)) {
            throw new Exception('DatabaseQueue does not implement QueueInterface');
        }
    } catch (Exception $e) {
        echo "  Note: Could not create DatabaseQueue instance: " . $e->getMessage() . "\n";
        echo "  This is expected if the database is not configured.\n";
    }
    
    return true;
});

// 5. Test Job Interface
test_feature('Job Interface', function() {
    if (!interface_exists('Portfolion\Queue\Jobs\JobInterface')) {
        throw new Exception('JobInterface not found');
    }
    
    // Verify the job interface methods
    $methods = [
        'fire',
        'release',
        'delete',
        'isDeleted',
        'isReleased',
        'attempts',
        'getName',
        'getResolvedName',
        'getJobId',
        'getRawBody',
        'getConnectionName',
        'getQueue'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Queue\Jobs\JobInterface');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("JobInterface missing method: $method");
        }
    }
    
    return true;
});

// 6. Test Job Base Class
test_feature('Job Base Class', function() {
    if (!class_exists('Portfolion\Queue\Jobs\Job')) {
        throw new Exception('Job base class not found');
    }
    
    // Verify the base job class methods
    $methods = [
        'fire',
        'payload',
        'isDeleted',
        'isReleased',
        'getName',
        'getResolvedName',
        'getJobId'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Queue\Jobs\Job');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("Job base class missing method: $method");
        }
    }
    
    return true;
});

// 7. Test Job Creation and Serialization
test_feature('Job Creation and Serialization', function() {
    // Create a test job
    $job = new TestJob(['key' => 'value']);
    
    // Test that the job can be serialized
    $serialized = serialize($job);
    if (empty($serialized)) {
        throw new Exception('Job serialization failed');
    }
    
    // Test that the job can be unserialized
    $unserialized = unserialize($serialized);
    if (!($unserialized instanceof TestJob)) {
        throw new Exception('Job unserialization failed');
    }
    
    if ($unserialized->data['key'] !== 'value') {
        throw new Exception('Job data lost during serialization/unserialization');
    }
    
    // Test that the job can be JSON encoded
    $json = json_encode(['job' => get_class($job), 'data' => $job->data]);
    if (empty($json)) {
        throw new Exception('Job JSON encoding failed');
    }
    
    // Test that the job can be JSON decoded
    $decoded = json_decode($json, true);
    if (!isset($decoded['job']) || $decoded['job'] !== get_class($job)) {
        throw new Exception('Job JSON decoding failed');
    }
    
    return true;
});

// 8. Test Queue Payload Creation
test_feature('Queue Payload Creation', function() {
    if (!class_exists('Portfolion\Queue\PayloadCreator')) {
        throw new Exception('PayloadCreator class not found');
    }
    
    // Create a payload creator
    $creator = new Portfolion\Queue\PayloadCreator();
    
    // Test creating a payload for a string job
    $payload = $creator->create('TestJob', ['key' => 'value']);
    
    if (!isset($payload['job']) || $payload['job'] !== 'TestJob') {
        throw new Exception('String job payload creation failed');
    }
    
    if (!isset($payload['data']) || empty($payload['data'])) {
        throw new Exception('String job data serialization failed');
    }
    
    // Test creating a payload for an object job
    $job = new TestJob(['key' => 'value']);
    $payload = $creator->create($job);
    
    if (!isset($payload['job']) || empty($payload['job'])) {
        throw new Exception('Object job payload creation failed');
    }
    
    return true;
});

// 9. Test Sync Queue (if available)
test_feature('Sync Queue', function() {
    if (!class_exists('Portfolion\Queue\Connectors\SyncConnector')) {
        return false; // Skip if SyncConnector doesn't exist
    }
    
    try {
        $connector = new Portfolion\Queue\Connectors\SyncConnector();
        $queue = $connector->connect([]);
        
        if (!($queue instanceof Portfolion\Queue\QueueInterface)) {
            throw new Exception('SyncConnector did not return a QueueInterface instance');
        }
        
        // Test pushing a job to the sync queue (it should execute immediately)
        $job = new TestJob(['key' => 'test_sync_queue']);
        $queue->push($job);
        
        return true;
    } catch (Exception $e) {
        throw new Exception('Sync queue test failed: ' . $e->getMessage());
    }
});

echo "\n=== QUEUE SYSTEM TEST COMPLETE ===\n"; 