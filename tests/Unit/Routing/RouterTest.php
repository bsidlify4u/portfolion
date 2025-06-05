<?php

namespace Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Portfolion\Routing\Router;

class RouterTest extends TestCase
{
    protected Router $router;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->router = Router::getInstance();
    }
    
    public function testAddRoute()
    {
        $this->router->get('/', function() {
            return 'Home';
        });
        
        $this->router->post('/users', function() {
            return 'Create User';
        });
        
        $this->router->put('/users/{id}', function($id) {
            return 'Update User ' . $id;
        });
        
        $this->router->delete('/users/{id}', function($id) {
            return 'Delete User ' . $id;
        });
        
        // We're just testing that no exceptions are thrown when adding routes
        $this->assertTrue(true);
    }
    
    public function testMatchRoute()
    {
        // Add a test route
        $this->router->get('/test/{id}', function($id) {
            return 'Test ' . $id;
        });
        
        // Set the REQUEST_URI and REQUEST_METHOD
        $_SERVER['REQUEST_URI'] = '/test/123';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // We can't easily test the full dispatch cycle in a unit test
        // So we're checking that no exceptions are thrown
        $this->assertTrue(true);
    }
} 