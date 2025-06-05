<?php

namespace Tests\Queue;

use PHPUnit\Framework\TestCase;
use Portfolion\Queue\SyncQueue;
use Portfolion\Contracts\Queue\ShouldQueue;

class SyncQueueTest extends TestCase
{
    /**
     * @var SyncQueue
     */
    protected $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = new SyncQueue();
    }

    /**
     * Test that the SyncQueue executes jobs immediately when pushed.
     */
    public function testSyncQueueExecutesJobsImmediately()
    {
        $executed = false;
        $job = new class($executed) implements ShouldQueue {
            private $executed;
            
            public function __construct(&$executed)
            {
                $this->executed = &$executed;
            }
            
            public function handle()
            {
                $this->executed = true;
            }
        };

        $this->queue->push($job);
        $this->assertTrue($executed, 'Job was not executed immediately');
    }

    /**
     * Test that the SyncQueue returns the correct queue name.
     */
    public function testSyncQueueReturnsCorrectQueueName()
    {
        $this->queue->setConnectionName('sync-test');
        $this->assertEquals('sync-test', $this->queue->getConnectionName());
    }

    /**
     * Test that the SyncQueue properly handles jobs that throw exceptions.
     */
    public function testSyncQueueHandlesExceptions()
    {
        $job = new class implements ShouldQueue {
            public $failed = false;
            
            public function handle()
            {
                throw new \Exception('Test exception');
            }
            
            public function failed(\Exception $e)
            {
                $this->failed = true;
            }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');
        
        $this->queue->push($job);
        
        // The job's failed method should have been called
        $this->assertTrue($job->failed);
    }

    /**
     * Test that the SyncQueue returns 0 for the size.
     */
    public function testSyncQueueSizeIsAlwaysZero()
    {
        $this->assertEquals(0, $this->queue->size());
        $this->assertEquals(0, $this->queue->size('default'));
    }

    /**
     * Test that the SyncQueue can handle delayed jobs.
     */
    public function testSyncQueueHandlesDelayedJobs()
    {
        $executed = false;
        $job = new class($executed) implements ShouldQueue {
            private $executed;
            
            public function __construct(&$executed)
            {
                $this->executed = &$executed;
            }
            
            public function handle()
            {
                $this->executed = true;
            }
        };

        // Even with a delay, SyncQueue should execute immediately
        $this->queue->later(60, $job);
        $this->assertTrue($executed, 'Delayed job was not executed immediately');
    }

    /**
     * Test that the SyncQueue properly resolves string-based jobs.
     */
    public function testSyncQueueResolvesStringJobs()
    {
        // Define a job class in the global namespace for testing
        if (!class_exists('SyncQueueTestJob')) {
            eval('
                class SyncQueueTestJob implements \Portfolion\Contracts\Queue\ShouldQueue {
                    public $data;
                    public static $executed = false;
                    
                    public function __construct($data = null) {
                        $this->data = $data;
                    }
                    
                    public function handle() {
                        self::$executed = true;
                    }
                }
            ');
        }
        
        // Reset the static property
        \SyncQueueTestJob::$executed = false;
        
        // Push a string-based job with some data
        $this->queue->push('SyncQueueTestJob', ['test' => 'data']);
        
        // The job should have been instantiated and executed
        $this->assertTrue(\SyncQueueTestJob::$executed, 'String-based job was not executed');
    }

    /**
     * Test that the SyncQueue properly handles bulk operations.
     */
    public function testSyncQueueHandlesBulkOperations()
    {
        $executed = [false, false, false];
        
        $jobs = [
            new class($executed[0]) implements ShouldQueue {
                private $executed;
                
                public function __construct(&$executed)
                {
                    $this->executed = &$executed;
                }
                
                public function handle()
                {
                    $this->executed = true;
                }
            },
            new class($executed[1]) implements ShouldQueue {
                private $executed;
                
                public function __construct(&$executed)
                {
                    $this->executed = &$executed;
                }
                
                public function handle()
                {
                    $this->executed = true;
                }
            },
            new class($executed[2]) implements ShouldQueue {
                private $executed;
                
                public function __construct(&$executed)
                {
                    $this->executed = &$executed;
                }
                
                public function handle()
                {
                    $this->executed = true;
                }
            }
        ];
        
        $result = $this->queue->bulk($jobs);
        
        $this->assertTrue($result, 'Bulk operation failed');
        $this->assertTrue($executed[0], 'First job in bulk was not executed');
        $this->assertTrue($executed[1], 'Second job in bulk was not executed');
        $this->assertTrue($executed[2], 'Third job in bulk was not executed');
    }

    /**
     * Test that other methods return expected values for SyncQueue.
     */
    public function testOtherSyncQueueMethods()
    {
        // Test pop method
        $this->assertNull($this->queue->pop());
        
        // Test delete method
        $this->assertTrue($this->queue->delete('dummy_job'));
        
        // Test release method
        $this->assertTrue($this->queue->release('dummy_job', 60));
        
        // Test clear method
        $this->assertTrue($this->queue->clear('default'));
    }
} 