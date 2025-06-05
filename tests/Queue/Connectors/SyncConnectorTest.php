<?php

namespace Tests\Queue\Connectors;

use PHPUnit\Framework\TestCase;
use Portfolion\Queue\Connectors\SyncConnector;
use Portfolion\Queue\SyncQueue;

class SyncConnectorTest extends TestCase
{
    /**
     * Test that the SyncConnector can create a SyncQueue instance.
     */
    public function testConnectReturnsASyncQueue()
    {
        $connector = new SyncConnector();
        $queue = $connector->connect([]);
        
        $this->assertInstanceOf(SyncQueue::class, $queue);
    }
    
    /**
     * Test that the SyncConnector ignores config values.
     */
    public function testConnectorIgnoresConfig()
    {
        $connector = new SyncConnector();
        
        // Providing config should still return a simple SyncQueue
        $queue1 = $connector->connect([]);
        $queue2 = $connector->connect(['nonexistent' => 'value']);
        
        // Both queues should be instances of SyncQueue
        $this->assertInstanceOf(SyncQueue::class, $queue1);
        $this->assertInstanceOf(SyncQueue::class, $queue2);
        
        // The queues should behave the same regardless of config
        $this->assertEquals(0, $queue1->size());
        $this->assertEquals(0, $queue2->size());
    }
} 