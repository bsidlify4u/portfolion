<?php

namespace Tests\Queue\Connectors;

use PHPUnit\Framework\TestCase;
use Portfolion\Queue\Connectors\ConnectorInterface;
use Portfolion\Queue\Connectors\SyncConnector;
use Portfolion\Queue\QueueInterface;

class ConnectorInterfaceTest extends TestCase
{
    /**
     * Test that the SyncConnector implements ConnectorInterface.
     */
    public function testSyncConnectorImplementsInterface()
    {
        $connector = new SyncConnector();
        
        $this->assertInstanceOf(ConnectorInterface::class, $connector);
    }
    
    /**
     * Test that the connector's connect method returns a QueueInterface.
     */
    public function testConnectorReturnsQueueInterface()
    {
        $connector = new SyncConnector();
        $queue = $connector->connect([]);
        
        $this->assertInstanceOf(QueueInterface::class, $queue);
    }
    
    /**
     * Test that the interface defines the correct methods.
     */
    public function testInterfaceDefinesCorrectMethods()
    {
        $reflection = new \ReflectionClass(ConnectorInterface::class);
        
        // Check if the connect method exists
        $this->assertTrue($reflection->hasMethod('connect'), 'ConnectorInterface is missing the connect method');
        
        // Check the connect method signature
        $method = $reflection->getMethod('connect');
        $this->assertTrue($method->isPublic(), 'The connect method should be public');
        
        // Check the method parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters, 'The connect method should have exactly 1 parameter');
        $this->assertEquals('config', $parameters[0]->getName(), 'The parameter should be named "config"');
        $this->assertTrue($parameters[0]->isArray(), 'The config parameter should be an array');
    }
    
    /**
     * Test all available connector implementations.
     */
    public function testAllAvailableConnectors()
    {
        // Define known connector implementations
        $connectorClasses = [
            SyncConnector::class,
            // Add other connector implementations as they become available
            // 'Portfolion\Queue\Connectors\DatabaseConnector',
            // 'Portfolion\Queue\Connectors\RedisConnector',
        ];
        
        foreach ($connectorClasses as $connectorClass) {
            if (!class_exists($connectorClass)) {
                $this->markTestSkipped("Connector class {$connectorClass} does not exist.");
                continue;
            }
            
            $connector = new $connectorClass();
            $this->assertInstanceOf(ConnectorInterface::class, $connector, "Connector {$connectorClass} should implement ConnectorInterface");
            
            $queue = $connector->connect([]);
            $this->assertInstanceOf(QueueInterface::class, $queue, "Queue returned by {$connectorClass} should implement QueueInterface");
        }
    }
} 