<?php

namespace Portfolion\Documentation;

use Portfolion\Routing\Router;
use Portfolion\Config;
use Portfolion\Http\Request;
use ReflectionClass;
use ReflectionMethod;

/**
 * OpenAPI documentation generator for API endpoints
 */
class OpenApiGenerator
{
    /**
     * @var Router
     */
    protected Router $router;
    
    /**
     * @var Config
     */
    protected Config $config;
    
    /**
     * @var array
     */
    protected array $documentation = [];
    
    /**
     * @var array
     */
    protected array $paths = [];
    
    /**
     * @var array
     */
    protected array $schemas = [];
    
    /**
     * Create a new OpenAPI generator instance
     * 
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->config = Config::getInstance();
        
        // Initialize the OpenAPI structure
        $this->initializeDocumentation();
    }
    
    /**
     * Initialize the base OpenAPI documentation structure
     */
    protected function initializeDocumentation(): void
    {
        $this->documentation = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $this->config->get('app.name', 'API Documentation'),
                'description' => $this->config->get('api.description', 'API Documentation for the application'),
                'version' => $this->config->get('api.version', '1.0.0'),
            ],
            'servers' => [
                [
                    'url' => $this->config->get('app.url', '/'),
                    'description' => 'API Server',
                ]
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => $this->getSecuritySchemes(),
            ],
        ];
        
        // Add security requirements if applicable
        if (!empty($this->documentation['components']['securitySchemes'])) {
            $this->documentation['security'] = [
                ['bearerAuth' => []],
            ];
        }
    }
    
    /**
     * Get security schemes configuration
     * 
     * @return array
     */
    protected function getSecuritySchemes(): array
    {
        $schemes = [];
        
        // Add Bearer token authentication if enabled
        if ($this->config->get('api.auth.bearer', true)) {
            $schemes['bearerAuth'] = [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ];
        }
        
        // Add OAuth2 if configured
        if ($this->config->get('api.auth.oauth2.enabled', false)) {
            $schemes['oauth2'] = [
                'type' => 'oauth2',
                'flows' => $this->config->get('api.auth.oauth2.flows', []),
            ];
        }
        
        // Add API key if configured
        if ($this->config->get('api.auth.apiKey.enabled', false)) {
            $schemes['apiKey'] = [
                'type' => 'apiKey',
                'in' => $this->config->get('api.auth.apiKey.in', 'header'),
                'name' => $this->config->get('api.auth.apiKey.name', 'X-API-Key'),
            ];
        }
        
        return $schemes;
    }
    
    /**
     * Generate the complete OpenAPI documentation
     * 
     * @return array The OpenAPI documentation
     */
    public function generate(): array
    {
        // Process all routes
        $this->processRoutes();
        
        // Add paths and schemas to the documentation
        $this->documentation['paths'] = $this->paths;
        $this->documentation['components']['schemas'] = $this->schemas;
        
        return $this->documentation;
    }
    
    /**
     * Process all routes to generate API documentation
     */
    protected function processRoutes(): void
    {
        $routes = $this->router->getRoutes();
        
        foreach ($routes as $route) {
            // Skip routes without handlers or not marked for API documentation
            if (!isset($route['handler']) || !$this->shouldDocumentRoute($route)) {
                continue;
            }
            
            $this->processRoute($route);
        }
    }
    
    /**
     * Determine if a route should be included in the API documentation
     * 
     * @param array $route Route configuration
     * @return bool
     */
    protected function shouldDocumentRoute(array $route): bool
    {
        // Check if the route has documentation annotations
        if (isset($route['documentation']) && $route['documentation'] === false) {
            return false;
        }
        
        // Skip non-API routes by default
        if (!$this->isApiRoute($route)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a route is an API route
     * 
     * @param array $route Route configuration
     * @return bool
     */
    protected function isApiRoute(array $route): bool
    {
        // Check for API prefix in the route path
        $apiPrefix = $this->config->get('api.prefix', 'api');
        
        if (strpos($route['path'], "/{$apiPrefix}/") === 0) {
            return true;
        }
        
        // Check if the route has an API middleware
        if (isset($route['middleware']) && in_array('api', (array) $route['middleware'])) {
            return true;
        }
        
        // Check if it's a controller that extends ApiController
        if (isset($route['handler']) && is_string($route['handler']) && strpos($route['handler'], '@') !== false) {
            list($controller, $method) = explode('@', $route['handler']);
            
            if (class_exists($controller)) {
                $reflection = new ReflectionClass($controller);
                if ($reflection->isSubclassOf('Portfolion\\Http\\ApiController')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Process a single route
     * 
     * @param array $route Route configuration
     */
    protected function processRoute(array $route): void
    {
        $path = $this->normalizePath($route['path']);
        $method = strtolower($route['method']);
        
        // Skip if the method is not a standard HTTP method
        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options'])) {
            return;
        }
        
        // Initialize the path if it doesn't exist
        if (!isset($this->paths[$path])) {
            $this->paths[$path] = [];
        }
        
        // Extract operation details from handler (controller/method)
        $operationDetails = $this->extractOperationDetails($route);
        
        // Add security requirements if needed
        if (isset($route['middleware']) && (
            in_array('auth', (array) $route['middleware']) ||
            in_array('auth:api', (array) $route['middleware'])
        )) {
            $operationDetails['security'] = [
                ['bearerAuth' => []]
            ];
        }
        
        // Add the operation to the path
        $this->paths[$path][$method] = $operationDetails;
    }
    
    /**
     * Normalize a route path to OpenAPI format
     * 
     * @param string $path Route path
     * @return string Normalized path
     */
    protected function normalizePath(string $path): string
    {
        // Convert Laravel/Symfony route parameters to OpenAPI format
        // From: /users/{id} or /users/:id
        // To:   /users/{id}
        $path = preg_replace('/:([^\/]+)/', '{$1}', $path);
        
        // Convert optional parameters
        // From: /users/{id?}
        // To:   /users/{id}
        $path = preg_replace('/\{([^\/]+)\?\}/', '{$1}', $path);
        
        return $path;
    }
    
    /**
     * Extract operation details from a route handler
     * 
     * @param array $route Route configuration
     * @return array Operation details
     */
    protected function extractOperationDetails(array $route): array
    {
        $details = [
            'tags' => [],
            'summary' => '',
            'description' => '',
            'operationId' => '',
            'parameters' => [],
            'responses' => [
                '200' => [
                    'description' => 'Successful operation',
                ],
                '400' => [
                    'description' => 'Bad request',
                ],
                '401' => [
                    'description' => 'Unauthorized',
                ],
                '404' => [
                    'description' => 'Not found',
                ],
                '422' => [
                    'description' => 'Validation error',
                ],
                '500' => [
                    'description' => 'Server error',
                ],
            ],
        ];
        
        // Parse handler documentation if available
        if (isset($route['handler']) && is_string($route['handler']) && strpos($route['handler'], '@') !== false) {
            list($controller, $method) = explode('@', $route['handler']);
            
            if (class_exists($controller) && method_exists($controller, $method)) {
                $reflectionMethod = new ReflectionMethod($controller, $method);
                $docComment = $reflectionMethod->getDocComment();
                
                if ($docComment) {
                    // Parse PHPDoc annotations
                    $this->parseDocBlock($docComment, $details);
                }
                
                // Extract request parameters from type-hinted request objects
                $this->extractRequestParameters($reflectionMethod, $details);
                
                // Generate operation ID if not explicitly set
                if (empty($details['operationId'])) {
                    $controllerName = (new ReflectionClass($controller))->getShortName();
                    $controllerName = str_replace('Controller', '', $controllerName);
                    $details['operationId'] = lcfirst($controllerName) . ucfirst($method);
                }
                
                // Add controller name as tag if no tags defined
                if (empty($details['tags'])) {
                    $controllerName = (new ReflectionClass($controller))->getShortName();
                    $controllerName = str_replace('Controller', '', $controllerName);
                    $details['tags'][] = $controllerName;
                }
            }
        }
        
        // Add parameters from route
        $this->extractRouteParameters($route, $details);
        
        return $details;
    }
    
    /**
     * Parse a PHPDoc block for OpenAPI annotations
     * 
     * @param string $docComment Doc comment
     * @param array $details Operation details to update
     */
    protected function parseDocBlock(string $docComment, array &$details): void
    {
        // Extract summary (first line)
        if (preg_match('|/\*\*\s*\n\s*\*\s*(.+)|', $docComment, $matches)) {
            $details['summary'] = trim($matches[1]);
        }
        
        // Extract description
        $description = [];
        preg_match_all('|^\s*\*\s*([^@\n].+)|m', $docComment, $matches);
        if (!empty($matches[1])) {
            // Skip the first line (summary)
            array_shift($matches[1]);
            $description = array_map('trim', $matches[1]);
            $details['description'] = implode("\n", $description);
        }
        
        // Extract tags
        preg_match_all('|^\s*\*\s*@tag\s+(.+)|m', $docComment, $matches);
        if (!empty($matches[1])) {
            $details['tags'] = array_map('trim', $matches[1]);
        }
        
        // Extract operation ID
        if (preg_match('|^\s*\*\s*@operationId\s+(.+)|m', $docComment, $matches)) {
            $details['operationId'] = trim($matches[1]);
        }
        
        // Extract responses
        preg_match_all('|^\s*\*\s*@response\s+(\d+)\s+(.+)|m', $docComment, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $statusCode = $match[1];
            $description = trim($match[2]);
            $details['responses'][$statusCode] = ['description' => $description];
        }
    }
    
    /**
     * Extract parameters from route definition
     * 
     * @param array $route Route configuration
     * @param array $details Operation details to update
     */
    protected function extractRouteParameters(array $route, array &$details): void
    {
        $path = $route['path'];
        
        // Extract path parameters
        preg_match_all('/\{([^\/\?]+)(\??)\}/', $path, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $name = $match[1];
            $required = empty($match[2]); // No ? means required
            
            $details['parameters'][] = [
                'name' => $name,
                'in' => 'path',
                'required' => $required,
                'schema' => [
                    'type' => 'string',
                ],
                'description' => 'The ' . str_replace('_', ' ', $name) . ' parameter',
            ];
        }
    }
    
    /**
     * Extract request parameters from method type hints
     * 
     * @param ReflectionMethod $method
     * @param array $details Operation details to update
     */
    protected function extractRequestParameters(ReflectionMethod $method, array &$details): void
    {
        $parameters = $method->getParameters();
        
        foreach ($parameters as $parameter) {
            if ($parameter->hasType()) {
                $type = $parameter->getType();
                $typeName = $type->getName();
                
                // Only process Request objects
                if ($type->isBuiltin() || !is_subclass_of($typeName, Request::class)) {
                    continue;
                }
                
                // For POST, PUT, PATCH methods, add request body
                $httpMethod = strtolower($details['method'] ?? '');
                if (in_array($httpMethod, ['post', 'put', 'patch'])) {
                    $details['requestBody'] = [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [],
                                ],
                            ],
                        ],
                    ];
                    
                    // Try to extract validation rules if the class has them
                    if (method_exists($typeName, 'rules')) {
                        $requestInstance = new $typeName();
                        $rules = $requestInstance->rules();
                        
                        $this->addPropertiesFromRules($rules, $details['requestBody']['content']['application/json']['schema']['properties']);
                    }
                }
                
                // Look for pagination parameters
                if ($httpMethod === 'get' && strpos($method->getDocComment(), '@paginated') !== false) {
                    $this->addPaginationParameters($details);
                }
            }
        }
    }
    
    /**
     * Add properties to schema based on validation rules
     * 
     * @param array $rules Validation rules
     * @param array $properties Properties to update
     */
    protected function addPropertiesFromRules(array $rules, array &$properties): void
    {
        foreach ($rules as $field => $rule) {
            // Skip nested fields for now
            if (strpos($field, '.') !== false) {
                continue;
            }
            
            $property = [
                'type' => 'string',
            ];
            
            // Convert rule to array if it's a string
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }
            
            // Process rules
            foreach ((array) $rule as $singleRule) {
                if (is_string($singleRule)) {
                    $this->processRule($singleRule, $property);
                }
            }
            
            $properties[$field] = $property;
        }
    }
    
    /**
     * Process a validation rule to determine property schema
     * 
     * @param string $rule Validation rule
     * @param array $property Property schema to update
     */
    protected function processRule(string $rule, array &$property): void
    {
        // Extract rule name and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleParams = isset($parts[1]) ? explode(',', $parts[1]) : [];
        
        switch ($ruleName) {
            case 'required':
                $property['required'] = true;
                break;
            case 'integer':
            case 'numeric':
                $property['type'] = 'integer';
                break;
            case 'boolean':
                $property['type'] = 'boolean';
                break;
            case 'array':
                $property['type'] = 'array';
                $property['items'] = ['type' => 'string'];
                break;
            case 'date':
            case 'datetime':
                $property['type'] = 'string';
                $property['format'] = 'date-time';
                break;
            case 'email':
                $property['type'] = 'string';
                $property['format'] = 'email';
                break;
            case 'url':
                $property['type'] = 'string';
                $property['format'] = 'uri';
                break;
            case 'min':
                if ($property['type'] === 'string') {
                    $property['minLength'] = (int) $ruleParams[0];
                } elseif ($property['type'] === 'integer') {
                    $property['minimum'] = (int) $ruleParams[0];
                } elseif ($property['type'] === 'array') {
                    $property['minItems'] = (int) $ruleParams[0];
                }
                break;
            case 'max':
                if ($property['type'] === 'string') {
                    $property['maxLength'] = (int) $ruleParams[0];
                } elseif ($property['type'] === 'integer') {
                    $property['maximum'] = (int) $ruleParams[0];
                } elseif ($property['type'] === 'array') {
                    $property['maxItems'] = (int) $ruleParams[0];
                }
                break;
            case 'in':
                $property['enum'] = $ruleParams;
                break;
        }
    }
    
    /**
     * Add pagination parameters to a GET operation
     * 
     * @param array $details Operation details to update
     */
    protected function addPaginationParameters(array &$details): void
    {
        $details['parameters'][] = [
            'name' => 'page',
            'in' => 'query',
            'required' => false,
            'schema' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
            'description' => 'Page number',
        ];
        
        $details['parameters'][] = [
            'name' => 'per_page',
            'in' => 'query',
            'required' => false,
            'schema' => [
                'type' => 'integer',
                'default' => 15,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'description' => 'Number of items per page',
        ];
        
        // Update response schema for pagination
        $okResponse = &$details['responses']['200'];
        $okResponse['content'] = [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                            ],
                        ],
                        'meta' => [
                            'type' => 'object',
                            'properties' => [
                                'current_page' => ['type' => 'integer'],
                                'last_page' => ['type' => 'integer'],
                                'per_page' => ['type' => 'integer'],
                                'total' => ['type' => 'integer'],
                            ],
                        ],
                        'links' => [
                            'type' => 'object',
                            'properties' => [
                                'first' => ['type' => 'string', 'format' => 'uri'],
                                'last' => ['type' => 'string', 'format' => 'uri'],
                                'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                                'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Export the documentation to a JSON file
     * 
     * @param string $path File path
     * @return bool Success indicator
     */
    public function exportJson(string $path): bool
    {
        $documentation = $this->generate();
        $json = json_encode($documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        return file_put_contents($path, $json) !== false;
    }
    
    /**
     * Export the documentation to a YAML file
     * 
     * @param string $path File path
     * @return bool Success indicator
     * @throws \RuntimeException If yaml extension is not available
     */
    public function exportYaml(string $path): bool
    {
        if (!function_exists('yaml_emit')) {
            throw new \RuntimeException('The YAML PHP extension is required to export as YAML');
        }
        
        $documentation = $this->generate();
        $yaml = yaml_emit($documentation, YAML_UTF8_ENCODING);
        
        return file_put_contents($path, $yaml) !== false;
    }
} 