<?php

namespace Tests\Routing;

use Tests\Http\HttpTestCase;
use Portfolion\Routing\Router;
use Portfolion\Http\Request;
use Portfolion\Http\Response;

class RouterTest extends HttpTestCase
{
    /**
     * @var Router
     */
    protected $router;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->router = new Router();
    }
    
    public function testBasicRouteRegistrationAndMatching()
    {
        // Register a route
        $this->router->get('/test', function () {
            return new Response('Test Route');
        });
        
        // Create a request
        $request = $this->get('/test');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response
        $this->assertResponseStatus($response, 200);
        $this->assertResponseContent($response, 'Test Route');
    }
    
    public function testRouteParameterMatching()
    {
        // Register a route with parameters
        $this->router->get('/users/{id}', function ($id) {
            return new Response("User ID: {$id}");
        });
        
        // Create a request
        $request = $this->get('/users/123');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response
        $this->assertResponseStatus($response, 200);
        $this->assertResponseContent($response, 'User ID: 123');
    }
    
    public function testMultipleRouteParameterMatching()
    {
        // Register a route with multiple parameters
        $this->router->get('/users/{id}/posts/{postId}', function ($id, $postId) {
            return new Response("User ID: {$id}, Post ID: {$postId}");
        });
        
        // Create a request
        $request = $this->get('/users/123/posts/456');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response
        $this->assertResponseStatus($response, 200);
        $this->assertResponseContent($response, 'User ID: 123, Post ID: 456');
    }
    
    public function testRouteWithTypeConstraints()
    {
        // Register a route with parameter type constraints
        $this->router->get('/users/{id}', function ($id) {
            return new Response("User ID: {$id}");
        })->where('id', '[0-9]+');
        
        // Create a request with a valid ID
        $request = $this->get('/users/123');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response
        $this->assertResponseStatus($response, 200);
        $this->assertResponseContent($response, 'User ID: 123');
        
        // Create a request with an invalid ID
        $request = $this->get('/users/abc');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response is a 404
        $this->assertResponseStatus($response, 404);
    }
    
    public function testRouteGroups()
    {
        // Register a route group
        $this->router->group(['prefix' => 'api'], function ($router) {
            $router->get('/users', function () {
                return new Response('API Users');
            });
            
            $router->get('/posts', function () {
                return new Response('API Posts');
            });
        });
        
        // Create requests
        $usersRequest = $this->get('/api/users');
        $postsRequest = $this->get('/api/posts');
        
        // Match and dispatch the routes
        $usersResponse = $this->router->dispatch($usersRequest);
        $postsResponse = $this->router->dispatch($postsRequest);
        
        // Verify the responses
        $this->assertResponseStatus($usersResponse, 200);
        $this->assertResponseContent($usersResponse, 'API Users');
        
        $this->assertResponseStatus($postsResponse, 200);
        $this->assertResponseContent($postsResponse, 'API Posts');
    }
    
    public function testNestedRouteGroups()
    {
        // Register nested route groups
        $this->router->group(['prefix' => 'api'], function ($router) {
            $router->group(['prefix' => 'v1'], function ($router) {
                $router->get('/users', function () {
                    return new Response('API v1 Users');
                });
            });
            
            $router->group(['prefix' => 'v2'], function ($router) {
                $router->get('/users', function () {
                    return new Response('API v2 Users');
                });
            });
        });
        
        // Create requests
        $v1Request = $this->get('/api/v1/users');
        $v2Request = $this->get('/api/v2/users');
        
        // Match and dispatch the routes
        $v1Response = $this->router->dispatch($v1Request);
        $v2Response = $this->router->dispatch($v2Request);
        
        // Verify the responses
        $this->assertResponseStatus($v1Response, 200);
        $this->assertResponseContent($v1Response, 'API v1 Users');
        
        $this->assertResponseStatus($v2Response, 200);
        $this->assertResponseContent($v2Response, 'API v2 Users');
    }
    
    public function testRouteGroupsWithMiddleware()
    {
        // Define a test middleware
        $middleware = function ($request, $next) {
            $response = $next($request);
            return new Response('Middleware: ' . $response->getContent());
        };
        
        // Register a route group with middleware
        $this->router->group(['middleware' => [$middleware]], function ($router) {
            $router->get('/test', function () {
                return new Response('Test');
            });
        });
        
        // Create a request
        $request = $this->get('/test');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response
        $this->assertResponseStatus($response, 200);
        $this->assertResponseContent($response, 'Middleware: Test');
    }
    
    public function testResourceRoutes()
    {
        // Register resource routes
        $this->router->resource('users', 'UserController');
        
        // Get all registered routes
        $routes = $this->getProperty($this->router, 'routes');
        
        // Verify that all resource routes were registered
        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('POST', $routes);
        $this->assertArrayHasKey('PUT', $routes);
        $this->assertArrayHasKey('DELETE', $routes);
        
        // Check for specific resource routes
        $getRoutes = array_keys($routes['GET']);
        $postRoutes = array_keys($routes['POST']);
        $putRoutes = array_keys($routes['PUT']);
        $deleteRoutes = array_keys($routes['DELETE']);
        
        $this->assertContains('/users', $getRoutes);         // index
        $this->assertContains('/users/create', $getRoutes);  // create
        $this->assertContains('/users/{id}', $getRoutes);    // show
        $this->assertContains('/users/{id}/edit', $getRoutes); // edit
        $this->assertContains('/users', $postRoutes);        // store
        $this->assertContains('/users/{id}', $putRoutes);    // update
        $this->assertContains('/users/{id}', $deleteRoutes); // destroy
    }
    
    public function testRouteNamedRoutes()
    {
        // Register a named route
        $this->router->get('/users/{id}', function ($id) {
            return new Response("User ID: {$id}");
        })->name('user.show');
        
        // Generate a URL for the named route
        $url = $this->router->url('user.show', ['id' => 123]);
        
        // Verify the URL
        $this->assertEquals('/users/123', $url);
    }
    
    public function testNotFoundResponse()
    {
        // Create a request for a non-existent route
        $request = $this->get('/not-found');
        
        // Match and dispatch the route
        $response = $this->router->dispatch($request);
        
        // Verify the response is a 404
        $this->assertResponseStatus($response, 404);
    }
} 