<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up any common test dependencies
    }
    
    /**
     * Clean up the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up any common test resources
        
        parent::tearDown();
    }
    
    /**
     * Assert that two arrays have the same values regardless of order.
     *
     * @param array $expected
     * @param array $actual
     * @param string $message
     * @return void
     */
    protected function assertArraySimilar(array $expected, array $actual, string $message = ''): void
    {
        sort($expected);
        sort($actual);
        
        $this->assertEquals($expected, $actual, $message);
    }
    
    /**
     * Create a mock for the given class and apply expectations.
     *
     * @param string $class
     * @param array $expectations
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mock(string $class, array $expectations = [])
    {
        $mock = $this->createMock($class);
        
        foreach ($expectations as $method => $return) {
            $mock->method($method)->willReturn($return);
        }
        
        return $mock;
    }
    
    /**
     * Call a protected or private method on an object.
     *
     * @param object $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    protected function callMethod(object $object, string $method, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
    
    /**
     * Get a protected or private property value from an object.
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected function getProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        
        return $property->getValue($object);
    }
    
    /**
     * Set a protected or private property value on an object.
     *
     * @param object $object
     * @param string $property
     * @param mixed $value
     * @return void
     */
    protected function setProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
} 