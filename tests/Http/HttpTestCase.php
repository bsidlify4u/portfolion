<?php

namespace Tests\Http;

use Tests\TestCase;
use Portfolion\Http\Request;
use Portfolion\Http\Response;

abstract class HttpTestCase extends TestCase
{
    /**
     * Make a mock request.
     *
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param string|null $content
     * @return Request
     */
    protected function createRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): Request {
        $server = array_merge([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'PHPUnit',
            'REMOTE_ADDR' => '127.0.0.1',
        ], $server);
        
        return new Request($parameters, $cookies, $files, $server, $content);
    }
    
    /**
     * Create a GET request.
     *
     * @param string $uri
     * @param array $parameters
     * @return Request
     */
    protected function get(string $uri, array $parameters = []): Request
    {
        return $this->createRequest('GET', $uri, $parameters);
    }
    
    /**
     * Create a POST request.
     *
     * @param string $uri
     * @param array $parameters
     * @return Request
     */
    protected function post(string $uri, array $parameters = []): Request
    {
        return $this->createRequest('POST', $uri, $parameters);
    }
    
    /**
     * Create a PUT request.
     *
     * @param string $uri
     * @param array $parameters
     * @return Request
     */
    protected function put(string $uri, array $parameters = []): Request
    {
        return $this->createRequest('PUT', $uri, $parameters);
    }
    
    /**
     * Create a DELETE request.
     *
     * @param string $uri
     * @param array $parameters
     * @return Request
     */
    protected function delete(string $uri, array $parameters = []): Request
    {
        return $this->createRequest('DELETE', $uri, $parameters);
    }
    
    /**
     * Assert that a response has a given status code.
     *
     * @param Response $response
     * @param int $status
     * @return void
     */
    protected function assertResponseStatus(Response $response, int $status): void
    {
        $this->assertEquals($status, $response->getStatusCode());
    }
    
    /**
     * Assert that a response has a given content.
     *
     * @param Response $response
     * @param string $content
     * @return void
     */
    protected function assertResponseContent(Response $response, string $content): void
    {
        $this->assertEquals($content, $response->getContent());
    }
    
    /**
     * Assert that a response contains a given string.
     *
     * @param Response $response
     * @param string $needle
     * @return void
     */
    protected function assertResponseContains(Response $response, string $needle): void
    {
        $this->assertStringContainsString($needle, $response->getContent());
    }
    
    /**
     * Assert that a response is JSON.
     *
     * @param Response $response
     * @return void
     */
    protected function assertResponseIsJson(Response $response): void
    {
        $this->assertJson($response->getContent());
    }
    
    /**
     * Assert that a response has a JSON structure.
     *
     * @param Response $response
     * @param array $structure
     * @return void
     */
    protected function assertResponseJsonStructure(Response $response, array $structure): void
    {
        $json = json_decode($response->getContent(), true);
        
        $this->assertNotNull($json, 'Response is not valid JSON');
        
        foreach ($structure as $key => $value) {
            if (is_array($value) && is_string($key)) {
                $this->assertArrayHasKey($key, $json);
                $this->assertResponseJsonStructure(
                    new Response(json_encode($json[$key])),
                    $value
                );
            } else {
                $key = is_numeric($key) ? $value : $key;
                $this->assertArrayHasKey($key, $json);
            }
        }
    }
} 