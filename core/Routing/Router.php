<?php
namespace Portfolion\Routing;

use Closure;
use InvalidArgumentException;
use Portfolion\Container\Container;
use Portfolion\Http\Request;
use Portfolion\Http\Response;
use RuntimeException;

class Router {
    private static ?self $instance = null;
    /** @var array<string, array<string, mixed>> */
    private array $routes = [];
    /** @var array<string, string> */
    private array $namedRoutes = [];
    /** @var array<int, string> */
    private array $globalMiddleware = [];
    /** @var array<string, string> */
    private array $routeMiddleware = [];
    /** @var array<string, array<string>> */
    private array $middlewareGroups = [];
    /** @var Closure|null */
    private $notFoundCallback = null;
    /** @var array<string, mixed>|null */
    private ?array $currentRoute = null;
    private Container $container;
    
    /**
     * Constructor.
     * 
     * Protected to allow testing while maintaining singleton pattern in production
     */
    protected function __construct() {
        $this->container = Container::getInstance();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Add global middleware.
     *
     * @param array<string>|string $middleware
     * @return $this
     */
    public function middleware(array|string $middleware): self {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }
        
        foreach ($middleware as $m) {
            if (!in_array($m, $this->globalMiddleware)) {
                $this->globalMiddleware[] = $m;
            }
        }
        
        return $this;
    }
    
    /**
     * Define a middleware group.
     *
     * @param string $name
     * @param array<string> $middleware
     * @return $this
     */
    public function middlewareGroup(string $name, array $middleware): self {
        $this->middlewareGroups[$name] = $middleware;
        return $this;
    }
    
    /**
     * Register a route for GET requests.
     *
     * @param string $uri
     * @param Closure|array{0: string, 1: string}|string $action
     * @return $this
     */
    public function get(string $uri, Closure|array|string $action): self {
        return $this->addRoute('GET', $uri, $action);
    }
    
    /**
     * Register a route for POST requests.
     *
     * @param string $uri
     * @param Closure|array{0: string, 1: string}|string $action
     * @return $this
     */
    public function post(string $uri, Closure|array|string $action): self {
        return $this->addRoute('POST', $uri, $action);
    }
    
    /**
     * Register a route for PUT requests.
     *
     * @param string $uri
     * @param Closure|array{0: string, 1: string}|string $action
     * @return $this
     */
    public function put(string $uri, Closure|array|string $action): self {
        return $this->addRoute('PUT', $uri, $action);
    }
    
    /**
     * Register a route for DELETE requests.
     *
     * @param string $uri
     * @param Closure|array{0: string, 1: string}|string $action
     * @return $this
     */
    public function delete(string $uri, Closure|array|string $action): self {
        return $this->addRoute('DELETE', $uri, $action);
    }
    
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request): Response {
        $method = $request->getMethod();
        $uri = $request->getUri();
        
        error_log("Dispatching request: $method $uri");
        
        foreach ($this->routes as $route => $handlers) {
            $pattern = $this->getRoutePattern($route);
            
            if (preg_match($pattern, $uri, $matches)) {
                error_log("Route matched: $route");
                
                if (isset($handlers[$method])) {
                    $parameters = $this->extractParameters($matches);
                    
                    $this->currentRoute = [
                        'uri' => $route,
                        'method' => $method,
                        'parameters' => $parameters,
                        'handler' => $handlers[$method]
                    ];
                    
                    // Set route parameters on the request object
                    $request->setParams($parameters);
                    
                    return $this->runRoute($request);
                }
            }
        }
        
        if ($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback, $request);
        }
        
        return new Response('Not Found', 404);
    }
    
    /**
     * Add a new route.
     *
     * @param string $method
     * @param string $uri
     * @param Closure|array{0: string, 1: string}|string $action
     * @return $this
     */
    private function addRoute(string $method, string $uri, Closure|array|string $action): self {
        $uri = '/' . trim($uri, '/');
        
        if (!isset($this->routes[$uri])) {
            $this->routes[$uri] = [];
        }
        
        $this->routes[$uri][$method] = $action;
        
        return $this;
    }
    
    /**
     * Convert route URI to regex pattern.
     *
     * @param string $route
     * @return string
     */
    private function getRoutePattern(string $route): string {
        // Replace {parameter} with a regex pattern to capture the parameter value
        // This will match any character except forward slashes
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
        
        // Add start and end anchors
        $pattern = '#^' . $pattern . '$#';
        
        error_log("Route pattern for $route: $pattern");
        
        return $pattern;
    }
    
    /**
     * Extract route parameters from matches.
     *
     * @param array<int|string, string> $matches
     * @return array<string, mixed>
     */
    private function extractParameters(array $matches): array {
        error_log("Raw matches: " . print_r($matches, true));
        
        $parameters = [];
        
        // Extract named parameters from the matches array
        foreach ($matches as $key => $value) {
            // Skip numeric keys (these are the full match and numeric captures)
            if (is_string($key) && $key !== 0) {
                // Convert numeric values to integers
                if (is_numeric($value)) {
                    $parameters[$key] = (int)$value;
                } else {
                    $parameters[$key] = $value;
                }
            }
        }
        
        error_log("Extracted parameters: " . print_r($parameters, true));
        
        return $parameters;
    }
    
    /**
     * Run the route handler.
     *
     * @param Request $request
     * @return Response
     * @throws RuntimeException
     */
    private function runRoute(Request $request): Response {
        $handler = $this->currentRoute['handler'];
        $parameters = $this->currentRoute['parameters'];
        
        error_log("Running route with parameters: " . print_r($parameters, true));
        
        // Extract individual parameters from the parameters array
        $id = isset($parameters['id']) ? (int)$parameters['id'] : null;
        
        error_log("ID parameter: " . ($id !== null ? $id : 'null'));
        
        if ($handler instanceof Closure) {
            return $handler($request, $parameters);
        }
        
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            
            // Create controller instance
            if ($controller === 'App\\Controllers\\TaskController') {
                $instance = new \App\Controllers\TaskController();
            } else {
                // Register controller as singleton if not already bound
                if (!$this->container->bound($controller)) {
                    $this->container->singleton($controller);
                }
                
                $instance = $this->container->make($controller);
            }
            
            // For TaskController methods that require an ID
            if ($controller === 'App\\Controllers\\TaskController' && 
                in_array($method, ['show', 'edit', 'update', 'destroy'])) {
                if ($id === null) {
                    throw new RuntimeException("ID parameter required for method $method");
                }
                
                error_log("Calling $controller::$method with ID: $id");
                return $instance->$method($request, $id);
            }
            
            // For other methods
            error_log("Calling $controller::$method without ID");
            return $instance->$method($request);
        }
        
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            
            // Create controller instance
            if ($controller === 'App\\Controllers\\TaskController') {
                $instance = new \App\Controllers\TaskController();
            } else {
                // Register controller as singleton if not already bound
                if (!$this->container->bound($controller)) {
                    $this->container->singleton($controller);
                }
                
                $instance = $this->container->make($controller);
            }
            
            // For TaskController methods that require an ID
            if ($controller === 'App\\Controllers\\TaskController' && 
                in_array($method, ['show', 'edit', 'update', 'destroy'])) {
                if ($id === null) {
                    throw new RuntimeException("ID parameter required for method $method");
                }
                
                error_log("Calling $controller::$method with ID: $id");
                return $instance->$method($request, $id);
            }
            
            // For other methods
            error_log("Calling $controller::$method without ID");
            return $instance->$method($request);
        }
        
        throw new RuntimeException("Invalid route handler");
    }
    
    /**
     * Set callback for handling 404 errors.
     *
     * @param Closure $callback
     * @return $this
     */
    public function notFound(Closure $callback): self {
        $this->notFoundCallback = $callback;
        return $this;
    }
    
    /**
     * Get the current route information.
     *
     * @return array<string, mixed>|null
     */
    public function getCurrentRoute(): ?array {
        return $this->currentRoute;
    }
    
    /**
     * Prevent cloning of the instance.
     *
     * @throws RuntimeException
     */
    private function __clone()
    {
        throw new RuntimeException('Router instances cannot be cloned');
    }
    
    /**
     * Prevent unserializing of the instance.
     *
     * @throws RuntimeException
     */
    public function __wakeup()
    {
        throw new RuntimeException('Router instances cannot be unserialized');
    }
}
