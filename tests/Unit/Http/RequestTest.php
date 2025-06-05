<?php

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Portfolion\Http\Request;

class RequestTest extends TestCase
{
    protected Request $request;
    protected array $originalPost;
    protected array $originalGet;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Store original superglobals
        $this->originalPost = $_POST;
        $this->originalGet = $_GET;
        
        // Initialize request with empty data
        $this->request = new Request();
    }
    
    protected function tearDown(): void
    {
        // Restore original superglobals
        $_POST = $this->originalPost;
        $_GET = $this->originalGet;
        parent::tearDown();
    }
    
    public function testGetMethod()
    {
        $this->assertIsString($this->request->getMethod());
    }
    
    public function testGetPath()
    {
        $this->assertIsString($this->request->getPath());
    }
    
    public function testSetAndGetParams()
    {
        $this->request->setParam('test_key', 'test_value');
        $this->assertEquals('test_value', $this->request->getParam('test_key'));
    }
    
    public function testValidation()
    {
        // Create test data
        $postData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        // Force POST method for testing
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Create a new request with the test data directly
        $request = new Request($postData);
        
        // Debug output
        echo "Request body: " . print_r($request->all(), true) . "\n";
        
        $rules = [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255'
        ];
        
        try {
            $validated = $request->validate($rules);
            $this->assertIsArray($validated);
            $this->assertArrayHasKey('name', $validated);
            $this->assertArrayHasKey('email', $validated);
            $this->assertEquals('John Doe', $validated['name']);
            $this->assertEquals('john@example.com', $validated['email']);
        } catch (\Exception $e) {
            $this->fail('Validation should not throw an exception with valid data: ' . $e->getMessage());
        }
    }
    
    public function testValidationFailsWithInvalidData()
    {
        // Create a new request with invalid data
        $request = new Request(['name' => '']);
        
        $rules = [
            'name' => 'required|max:255'
        ];
        
        $this->expectException(\InvalidArgumentException::class);
        $request->validate($rules);
    }
    
    public function testGetAndSetMultipleParams()
    {
        $params = [
            'id' => 1,
            'name' => 'Test',
            'active' => true
        ];
        
        $this->request->setParams($params);
        
        $this->assertEquals($params, $this->request->getParams());
        $this->assertEquals(1, $this->request->getParam('id'));
        $this->assertEquals('Test', $this->request->getParam('name'));
        $this->assertEquals(true, $this->request->getParam('active'));
    }
    
    public function testAllReturnsAllInputs()
    {
        // Test with direct data in constructor
        $_SERVER['REQUEST_METHOD'] = 'POST';  // Ensure POST method
        $request = new Request(['name' => 'John'], ['page' => '1']);
        
        // Debug output
        echo "Request all: " . print_r($request->all(), true) . "\n";
        
        $all = $request->all();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('name', $all);
        $this->assertArrayHasKey('page', $all);
        $this->assertEquals('John', $all['name']);
        $this->assertEquals('1', $all['page']);
    }
} 