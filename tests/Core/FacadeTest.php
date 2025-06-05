<?php

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use Portfolion\Support\Facades\Facade;

class FacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear resolved instances before each test
        $this->clearResolvedInstances();
    }
    
    protected function tearDown(): void
    {
        // Clear resolved instances after each test
        $this->clearResolvedInstances();
        
        parent::tearDown();
    }
    
    /**
     * Clear the resolved instances in Facade.
     */
    protected function clearResolvedInstances()
    {
        $reflection = new \ReflectionClass(Facade::class);
        $property = $reflection->getProperty('resolvedInstance');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
    
    /**
     * Test that a Facade can properly retrieve its root object.
     */
    public function testFacadeRetrievesRootObject()
    {
        $root = TestFacade::getFacadeRoot();
        
        $this->assertInstanceOf(\stdClass::class, $root);
        $this->assertEquals('value', $root->property);
    }
    
    /**
     * Test that a Facade caches its resolved instance.
     */
    public function testFacadeCachesResolvedInstance()
    {
        $root1 = TestFacade::getFacadeRoot();
        $root2 = TestFacade::getFacadeRoot();
        
        $this->assertSame($root1, $root2);
    }
    
    /**
     * Test that a Facade correctly forwards calls to the underlying instance.
     */
    public function testFacadeForwardsCallsToRoot()
    {
        // Create a mock object that we can assert against
        $mock = $this->createMock(\stdClass::class);
        $mock->property = 'value';
        $mock->expects($this->once())
             ->method('callMethod')
             ->with('test')
             ->willReturn('test');
        
        // Set up our TestFacade to use this mock
        TestFacade::setTestInstance($mock);
        
        // Call the method on the facade
        $result = TestFacade::callMethod('test');
        
        // Verify the result
        $this->assertEquals('test', $result);
    }
    
    /**
     * Test that a Facade throws exception when no root object is available.
     */
    public function testFacadeThrowsExceptionWhenNoRootObjectIsAvailable()
    {
        // We need to override the app() function since we're testing in isolation
        // This test will use our NoAppFacade that doesn't rely on app()
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A facade root has not been set.');
        
        NoAppFacade::missingMethod();
    }
}

/**
 * A test facade implementation for testing purposes.
 */
class TestFacade extends Facade
{
    protected static $testInstance;
    
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'test.object';
    }
    
    /**
     * Set a test instance for the facade.
     */
    public static function setTestInstance($instance)
    {
        static::$testInstance = $instance;
        static::$resolvedInstance['test.object'] = $instance;
    }
    
    /**
     * Override the resolveFacadeInstance method to return a test object.
     */
    protected static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        
        $object = new \stdClass();
        $object->property = 'value';
        
        return static::$resolvedInstance[$name] = $object;
    }
}

/**
 * A facade that will always throw an exception for testing.
 */
class NoAppFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'invalid.object';
    }
    
    /**
     * Override to not use app() function.
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }
        
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        
        throw new \RuntimeException('A facade root has not been set.');
    }
} 