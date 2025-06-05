<?php

namespace Tests\Queue;

use PHPUnit\Framework\TestCase;
use Portfolion\Queue\PayloadCreator;
use Portfolion\Contracts\Queue\ShouldQueue;

class PayloadCreatorTest extends TestCase
{
    /**
     * @var PayloadCreator
     */
    protected $payloadCreator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payloadCreator = new PayloadCreator();
    }

    /**
     * Test creating a payload for a string-based job.
     */
    public function testCreateStringPayload()
    {
        $job = 'TestJob';
        $data = ['key' => 'value'];
        $payload = $this->payloadCreator->create($job, $data);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('job', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('attempts', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('created_at', $payload);

        $this->assertEquals($job, $payload['job']);
        $this->assertEquals(0, $payload['attempts']);
        $this->assertTrue(is_string($payload['id']));
        $this->assertTrue(is_int($payload['created_at']));

        // Verify the data was serialized
        $this->assertEquals(serialize($data), $payload['data']);
    }

    /**
     * Test creating a payload for an object-based job.
     */
    public function testCreateObjectPayload()
    {
        $job = new TestQueueJob();
        $payload = $this->payloadCreator->create($job);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('job', $payload);
        $this->assertArrayHasKey('attempts', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('created_at', $payload);

        $this->assertEquals(0, $payload['attempts']);
        $this->assertTrue(is_string($payload['id']));
        $this->assertTrue(is_int($payload['created_at']));

        // Verify the job was serialized
        $unserializedJob = unserialize($payload['job']);
        $this->assertInstanceOf(TestQueueJob::class, $unserializedJob);
    }

    /**
     * Test that a random ID is generated for each job.
     */
    public function testRandomIdGeneration()
    {
        $job1 = 'TestJob';
        $job2 = 'TestJob';
        
        $payload1 = $this->payloadCreator->create($job1);
        $payload2 = $this->payloadCreator->create($job2);

        $this->assertNotEquals($payload1['id'], $payload2['id']);
    }

    /**
     * Test that the timestamp is correctly set.
     */
    public function testTimestampGeneration()
    {
        $now = time();
        $job = 'TestJob';
        
        $payload = $this->payloadCreator->create($job);

        // The timestamp should be close to the current time
        $this->assertGreaterThanOrEqual($now - 1, $payload['created_at']);
        $this->assertLessThanOrEqual($now + 1, $payload['created_at']);
    }

    /**
     * Test that attempt count starts at zero.
     */
    public function testAttemptsStartAtZero()
    {
        $job = 'TestJob';
        $payload = $this->payloadCreator->create($job);

        $this->assertEquals(0, $payload['attempts']);
    }
}

/**
 * A test job implementation for testing.
 */
class TestQueueJob implements ShouldQueue
{
    public function handle()
    {
        // Test job implementation
    }
} 