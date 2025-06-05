<?php
namespace Portfolion\Http;

use InvalidArgumentException;
use RuntimeException;

class Request {
    /**
     * Route parameters
     * @var array<string, mixed>
     */
    private array $params = [];
    
    /**
     * Query string parameters
     * @var array<string, mixed>
     */
    private array $query = [];
    
    /**
     * Request body parameters
     * @var array<string, mixed>
     */
    private array $body = [];
    
    /**
     * Uploaded files
     * @var array<string, array>
     */
    private array $files = [];
    
    /**
     * Request headers
     * @var array<string, string>
     */
    private array $headers = [];
    
    /**
     * Request method
     */
    private string $method;
    
    /**
     * Request URI
     */
    private string $uri;
    
    /**
     * Current route name
     */
    private ?string $route = null;
    
    /**
     * Custom attributes
     * @var array<string, mixed>
     */
    private array $attributes = [];
    
    /**
     * Raw request body
     */
    private ?string $rawBody = null;
    
    /**
     * Parsed request body
     * @var array<string, mixed>
     */
    private array $parsedBody = [];
    
    /**
     * Create a new request instance
     * 
     * @param array<string, mixed> $postData Optional POST data for testing
     * @param array<string, mixed> $getData Optional GET data for testing
     */
    public function __construct(array $postData = [], array $getData = []) {
        $this->initializeRequest($postData, $getData);
    }
    
    /**
     * Get a route parameter value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed {
        return $this->params[$key] ?? $default;
    }
    
    /**
     * Set a route parameter value
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setParam(string $key, mixed $value): void {
        $this->params[$key] = $value;
    }
    
    /**
     * Get all route parameters
     * 
     * @return array<string, mixed>
     */
    public function getParams(): array {
        return $this->params;
    }
    
    /**
     * Set multiple route parameters
     * 
     * @param array<string, mixed> $params
     */
    public function setParams(array $params): void {
        $this->params = array_merge($this->params, $params);
    }
    
    /**
     * Initialize the request from globals or test data
     * 
     * @param array<string, mixed> $postData Optional POST data for testing
     * @param array<string, mixed> $getData Optional GET data for testing
     */
    private function initializeRequest(array $postData = [], array $getData = []): void {
        $this->method = $this->getRequestMethod();
        $this->uri = $this->getRequestUri();
        
        // Use provided test data or global data
        $queryData = !empty($getData) ? $getData : $_GET;
        $this->query = $this->sanitizeInput($queryData);
        
        $this->headers = $this->getRequestHeaders();
        
        // Handle different request methods and use provided test data if available
        if ($this->method === 'GET') {
            $this->body = [];
        } elseif ($this->method === 'POST') {
            $bodyData = !empty($postData) ? $postData : $_POST;
            $this->body = $this->sanitizeInput($bodyData);
            $this->files = $_FILES;
        } else {
            if (!empty($postData)) {
                // For testing with custom methods
                $this->body = $this->sanitizeInput($postData);
            } else {
                $this->parseRequestBody();
            }
        }
    }
    
    private function getRequestMethod(): string {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Check for method override in headers or POST data
        if ($method === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] 
                ?? $_POST['_method'] 
                ?? '';
                
            if (in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'], true)) {
                $method = strtoupper($override);
            }
        }
        
        return $method;
    }
    
    private function getRequestUri(): string {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $uri ?: '/';
    }
    
    private function getRequestHeaders(): array {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $key => $value) {
                if (strncmp($key, 'HTTP_', 5) === 0) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $headers[$name] = $value;
                } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                    $name = str_replace('_', '-', $key);
                    $headers[$name] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    private function parseRequestBody(): void {
        $contentType = $this->getHeader('Content-Type');
        $this->rawBody = file_get_contents('php://input');
        
        if (strpos($contentType, 'application/json') !== false) {
            $this->parsedBody = json_decode($this->rawBody, true) ?? [];
            $this->body = $this->sanitizeInput($this->parsedBody);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($this->rawBody, $data);
            $this->body = $this->sanitizeInput($data);
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $this->body = $this->sanitizeInput($_POST);
            $this->files = $_FILES;
        }
    }
    
    private function sanitizeInput(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                // Don't convert to string for testing, only sanitize
                $sanitized[$key] = is_string($value) ? $this->sanitizeValue($value) : $value;
            }
        }
        return $sanitized;
    }
    
    private function sanitizeValue($value): string {
        if (is_string($value)) {
            // Remove null bytes and strip invalid UTF-8 characters
            $value = str_replace(chr(0), '', $value);
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return (string) $value;
    }
    
    public function getMethod(): string {
        return $this->method;
    }
    
    public function getPath(): string {
        return $this->uri;
    }
    
    public function getUri(): string {
        return $this->uri;
    }
    
    public function get(string $key, $default = null) {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }
    
    public function input(string $key, $default = null) {
        return $this->get($key, $default);
    }
    
    public function query(string $key, $default = null) {
        return $this->query[$key] ?? $default;
    }
    
    public function post(string $key, $default = null) {
        return $this->body[$key] ?? $default;
    }
    
    public function json(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->parsedBody;
        }
        
        return $this->parsedBody[$key] ?? $default;
    }
    
    public function getRawBody(): string {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input');
        }
        return $this->rawBody;
    }
    
    public function all(): array {
        return array_merge($this->query, $this->body);
    }
    
    public function only(array $keys): array {
        return array_intersect_key($this->all(), array_flip($keys));
    }
    
    public function except(array $keys): array {
        return array_diff_key($this->all(), array_flip($keys));
    }
    
    public function has(string $key): bool {
        return isset($this->query[$key]) || isset($this->body[$key]);
    }
    
    public function hasAny(array $keys): bool {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }
    
    public function filled(string $key): bool {
        $value = $this->get($key);
        return $value !== null && $value !== '';
    }
    
    public function missing(string $key): bool {
        return !$this->has($key);
    }
    
    public function file(string $key) {
        return $this->files[$key] ?? null;
    }
    
    public function hasFile(string $key): bool {
        return isset($this->files[$key]) && 
               $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }
    
    public function getHeader(string $key, $default = null): ?string {
        return $this->headers[$key] ?? $default;
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }
    
    public function getIp(): string {
        $ipSources = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipSources as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    public function isSecure(): bool {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 
            $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && 
            $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        return isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443;
    }
    
    public function isXmlHttpRequest(): bool {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
    
    public function isJson(): bool {
        return strpos($this->getHeader('Content-Type', ''), 'application/json') !== false;
    }
    
    public function expectsJson(): bool {
        return $this->isJson() || $this->isXmlHttpRequest();
    }
    
    public function wantsJson(): bool {
        return strpos($this->getHeader('Accept', ''), 'application/json') !== false;
    }
    
    public function setRoute(string $route): void {
        $this->route = $route;
    }
    
    public function getRoute(): ?string {
        return $this->route;
    }
    
    public function setAttribute(string $key, $value): void {
        $this->attributes[$key] = $value;
    }
    
    public function getAttribute(string $key, $default = null) {
        return $this->attributes[$key] ?? $default;
    }
    
    public function getAttributes(): array {
        return $this->attributes;
    }
    
    /**
     * Validate request data against a set of rules
     *
     * @param array $rules The validation rules
     * @return array The validated data
     * @throws \InvalidArgumentException If validation fails
     */
    public function validate(array $rules): array
    {
        $errors = [];
        $validated = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $this->get($field);
            $validated[$field] = $value;
            
            foreach ($fieldRules as $rule) {
                if ($rule === 'required') {
                    if ($value === null || $value === '') {
                        $errors[$field][] = "The {$field} field is required.";
                        continue 2; // Skip other validations if required fails
                    }
                } elseif (strpos($rule, 'max:') === 0) {
                    $max = (int) substr($rule, 4);
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $errors[$field][] = "The {$field} field may not be greater than {$max} characters.";
                    }
                } elseif (strpos($rule, 'min:') === 0) {
                    $min = (int) substr($rule, 4);
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $errors[$field][] = "The {$field} field must be at least {$min} characters.";
                    }
                } elseif (strpos($rule, 'in:') === 0) {
                    $allowedValues = explode(',', substr($rule, 3));
                    if ($value !== null && $value !== '' && !in_array($value, $allowedValues)) {
                        $errors[$field][] = "The {$field} field must be one of: " . implode(', ', $allowedValues);
                    }
                } elseif ($rule === 'integer') {
                    if ($value !== null && $value !== '' && !is_numeric($value)) {
                        $errors[$field][] = "The {$field} field must be an integer.";
                    } else if ($value !== null && $value !== '') {
                        // Convert to integer
                        $validated[$field] = (int) $value;
                    }
                } elseif ($rule === 'numeric') {
                    if ($value !== null && !is_numeric($value)) {
                        $errors[$field][] = "The {$field} field must be numeric.";
                    }
                } elseif ($rule === 'date') {
                    if ($value !== null && $value !== '' && strtotime($value) === false) {
                        $errors[$field][] = "The {$field} field must be a valid date.";
                    }
                } elseif ($rule === 'nullable') {
                    if ($value === null || $value === '') {
                        continue 2; // Skip other validations if field is nullable and empty
                    }
                } elseif ($rule === 'email') {
                    if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "The {$field} field must be a valid email address.";
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            // Format error messages
            $errorMessages = [];
            foreach ($errors as $field => $fieldErrors) {
                $errorMessages[] = implode(' ', $fieldErrors);
            }
            
            throw new InvalidArgumentException('Validation failed: ' . implode(' ', $errorMessages));
        }
        
        return $validated;
    }
}
