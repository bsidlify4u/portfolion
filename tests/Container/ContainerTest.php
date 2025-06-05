<?php

namespace Tests\Container;

use Tests\TestCase;
use Portfolion\Container\Container;

// Test classes for dependency injection
class TestService
{
    public function getName()
    {
        return 'TestService';
    }
}

class DependentService
{
    protected $service;
    
    public function __construct(TestService $service)
    {
        $this->service = $service;
    }
    
    public function getServiceName()
    {
        return $this->service->getName();
    }
}

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    protected $container;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = new Container();
    }
    
    public function testBindAndResolve()
    {
        // Bind a value
        $this->container->bind('key', 'value');
        
        // Resolve the value
        $value = $this->container->resolve('key');
        
        // Verify the value
        $this->assertEquals('value', $value);
    }
    
    public function testBindSingleton()
    {
        // Bind a singleton
        $this->container->singleton('service', function () {
            return new TestService();
        });
        
        // Resolve the singleton twice
        $service1 = $this->container->resolve('service');
        $service2 = $this->container->resolve('service');
        
        // Verify both instances are the same
        $this->assertSame($service1, $service2);
    }
    
    public function testBindInstance()
    {
        // Create an instance
        $service = new TestService();
        
        // Bind the instance
        $this->container->instance('service', $service);
        
        // Resolve the instance
        $resolved = $this->container->resolve('service');
        
        // Verify the instance
        $this->assertSame($service, $resolved);
    }
    
    public function testResolveClass()
    {
        // Resolve a class
        $service = $this->container->make(TestService::class);
        
        // Verify the instance
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertEquals('TestService', $service->getName());
    }
    
    public function testResolveWithDependencies()
    {
        // Bind the test service
        $this->container->bind(TestService::class, function () {
            return new TestService();
        });
        
        // Resolve a class with dependencies
        $dependent = $this->container->make(DependentService::class);
        
        // Verify the instance and its dependencies
        $this->assertInstanceOf(DependentService::class, $dependent);
        $this->assertEquals('TestService', $dependent->getServiceName());
    }
    
    public function testContextualBinding()
    {
        // Create a special service
        $specialService = new class extends TestService {
            public function getName()
            {
                return 'SpecialService';
            }
        };
        
        // Bind a contextual dependency
        $this->container->bindWhen(DependentService::class)
                        ->needs(TestService::class)
                        ->give(function () use ($specialService) {
                            return $specialService;
                        });
        
        // Resolve a class with contextual binding
        $dependent = $this->container->make(DependentService::class);
        
        // Verify the contextual binding was used
        $this->assertEquals('SpecialService', $dependent->getServiceName());
    }
    
    public function testHas()
    {
        // Nothing bound yet
        $this->assertFalse($this->container->has('key'));
        
        // Bind a value
        $this->container->bind('key', 'value');
        
        // Now it should be bound
        $this->assertTrue($this->container->has('key'));
    }
    
    public function testCallWithDependencyInjection()
    {
        // Define a function that requires dependencies
        $callback = function (TestService $service) {
            return $service->getName();
        };
        
        // Call the function with dependency injection
        $result = $this->container->call($callback);
        
        // Verify the result
        $this->assertEquals('TestService', $result);
    }
    
    public function testBindingOverride()
    {
        // Bind a value
        $this->container->bind('key', 'original');
        
        // Override the binding
        $this->container->bind('key', 'override');
        
        // Resolve the value
        $value = $this->container->resolve('key');
        
        // Verify the value was overridden
        $this->assertEquals('override', $value);
    }
} 