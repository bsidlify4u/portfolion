<?php

namespace Tests\Queue;

use PHPUnit\Framework\TestCase;
use Portfolion\Contracts\Queue\ShouldQueue;

class ShouldQueueInterfaceTest extends TestCase
{
    /**
     * Test that the ShouldQueue interface defines the required methods.
     */
    public function testInterfaceDefinesRequiredMethods()
    {
        $reflection = new \ReflectionClass(ShouldQueue::class);
        
        // Check if it's an interface
        $this->assertTrue($reflection->isInterface(), 'ShouldQueue should be an interface');
        
        // Check if the handle method exists
        $this->assertTrue($reflection->hasMethod('handle'), 'ShouldQueue is missing the handle method');
        
        // Check the handle method signature
        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic(), 'The handle method should be public');
        
        // Check the method parameters
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters, 'The handle method should have no parameters');
        
        // Check that the return type is void (PHP 7.1+)
        if (PHP_VERSION_ID >= 70100) {
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                $this->assertEquals('void', (string) $returnType, 'The handle method should return void');
            }
        }
    }
    
    /**
     * Test that a job implementation implements the ShouldQueue interface.
     */
    public function testJobImplementation()
    {
        $job = new TestQueueableJob();
        
        $this->assertInstanceOf(ShouldQueue::class, $job);
        
        // Test that the handle method can be called
        $result = $job->handle();
        $this->assertNull($result, 'The handle method should return void');
        $this->assertTrue($job->wasHandled, 'The handle method was not called');
    }
    
    /**
     * Test job serialization and deserialization.
     */
    public function testJobSerialization()
    {
        $job = new TestQueueableJob('test data');
        
        // Serialize and unserialize the job
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);
        
        $this->assertInstanceOf(ShouldQueue::class, $unserialized);
        $this->assertInstanceOf(TestQueueableJob::class, $unserialized);
        $this->assertEquals('test data', $unserialized->getData());
        
        // Test that the handle method can be called on the unserialized job
        $unserialized->handle();
        $this->assertTrue($unserialized->wasHandled, 'The handle method was not called on unserialized job');
    }
}

/**
 * A test implementation of the ShouldQueue interface.
 */
class TestQueueableJob implements ShouldQueue
{
    /**
     * @var mixed
     */
    private $data;
    
    /**
     * @var bool
     */
    public $wasHandled = false;
    
    /**
     * Create a new job instance.
     *
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }
    
    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->wasHandled = true;
    }
    
    /**
     * Get the job data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
} 