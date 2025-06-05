<?php
/**
 * Advanced Router and URL handling system
 */

class Router {
    private static $instance = null;
    private $routes = [];
    private $notFoundCallback;
    private $beforeMiddleware = [];
    private $afterMiddleware = [];
    private $currentRoute = null;
    private $baseUrl;
    
    private function __construct() {
        $this->baseUrl = $this->getBaseUrl();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get base URL of the application
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);
        return rtrim($protocol . $host . $baseDir, '/');
    }
    
    /**
     * Add route with support for dynamic parameters
     */
    public function add($method, $pattern, $callback, $name = null) {
        $pattern = '/^' . str_replace(['/', '{', '}'], ['\/', '(?<', '>[^\/]+)'], $pattern) . '$/';
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
            'name' => $name
        ];
        return $this;
    }
    
    /**
     * Add GET route
     */
    public function get($pattern, $callback, $name = null) {
        return $this->add('GET', $pattern, $callback, $name);
    }
    
    /**
     * Add POST route
     */
    public function post($pattern, $callback, $name = null) {
        return $this->add('POST', $pattern, $callback, $name);
    }
    
    /**
     * Set 404 handler
     */
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
        return $this;
    }
    
    /**
     * Add before middleware
     */
    public function before($callback) {
        $this->beforeMiddleware[] = $callback;
        return $this;
    }
    
    /**
     * Add after middleware
     */
    public function after($callback) {
        $this->afterMiddleware[] = $callback;
        return $this;
    }
    
    /**
     * Generate URL for named route
     */
    public function url($name, $params = []) {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                $url = preg_replace_callback('/\{([^}]+)\}/', function($match) use ($params) {
                    return $params[$match[1]] ?? '';
                }, $route['pattern']);
                return $this->baseUrl . str_replace('\\/', '/', $url);
            }
        }
        throw new Exception("Route '{$name}' not found");
    }
    
    /**
     * Dispatch the request
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        $uri = '/' . trim($uri, '/');
        
        // Run before middleware
        foreach ($this->beforeMiddleware as $middleware) {
            $result = call_user_func($middleware, $uri, $method);
            if ($result === false) return;
        }
        
        // Match routes
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $this->currentRoute = $route;
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $result = call_user_func_array($route['callback'], $params);
                
                // Run after middleware
                foreach ($this->afterMiddleware as $middleware) {
                    call_user_func($middleware, $uri, $method, $result);
                }
                
                return $result;
            }
        }
        
        // Handle 404
        if ($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback, $uri);
        }
        
        header("HTTP/1.0 404 Not Found");
        return '404 Not Found';
    }
    
    /**
     * Get current route
     */
    public function getCurrentRoute() {
        return $this->currentRoute;
    }
    
    /**
     * Redirect to named route
     */
    public function redirect($name, $params = []) {
        $url = $this->url($name, $params);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Check if current route matches name
     */
    public function isRoute($name) {
        return $this->currentRoute && $this->currentRoute['name'] === $name;
    }
    
    /**
     * Get query parameters
     */
    public function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get post parameters
     */
    public function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
}
