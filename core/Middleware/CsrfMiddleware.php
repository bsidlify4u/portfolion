<?php

namespace Portfolion\Middleware;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Session\Session;
use Portfolion\Error\SecurityException;

/**
 * Middleware for CSRF protection
 * 
 * This middleware validates CSRF tokens for state-changing HTTP requests.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @var array HTTP methods that require CSRF validation
     */
    protected array $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];
    
    /**
     * @var array Routes that are excluded from CSRF validation
     */
    protected array $except = [];
    
    /**
     * Create a new CSRF middleware instance
     * 
     * @param array $except Routes to exclude from CSRF validation
     */
    public function __construct(array $except = [])
    {
        $this->except = $except;
    }
    
    /**
     * Process the request and response
     * 
     * @param Request $request The HTTP request
     * @param callable $next The next middleware
     * @return Response The HTTP response
     * @throws SecurityException If the CSRF token is invalid
     */
    public function process(Request $request, callable $next): Response
    {
        // Skip CSRF validation for non-state-changing requests
        if (!$this->shouldValidateRequest($request)) {
            return $next($request);
        }
        
        // Validate the CSRF token
        $this->validateCsrfToken($request);
        
        // Process the request
        return $next($request);
    }
    
    /**
     * Determine if the request should be validated
     * 
     * @param Request $request The HTTP request
     * @return bool Whether the request should be validated
     */
    protected function shouldValidateRequest(Request $request): bool
    {
        // Skip validation for excluded routes
        foreach ($this->except as $route) {
            if ($request->matches($route)) {
                return false;
            }
        }
        
        // Only validate state-changing requests
        return in_array($request->getMethod(), $this->methods);
    }
    
    /**
     * Validate the CSRF token
     * 
     * @param Request $request The HTTP request
     * @return void
     * @throws SecurityException If the CSRF token is invalid
     */
    protected function validateCsrfToken(Request $request): void
    {
        $token = $this->getTokenFromRequest($request);
        $session = Session::getInstance();
        
        // Check if the token exists in the session
        if (!$session->has('_csrf_token')) {
            throw new SecurityException('CSRF token missing from session.');
        }
        
        // Check if the token matches
        if (!hash_equals($session->get('_csrf_token'), $token)) {
            throw new SecurityException('CSRF token mismatch.');
        }
    }
    
    /**
     * Get the CSRF token from the request
     * 
     * @param Request $request The HTTP request
     * @return string The CSRF token
     * @throws SecurityException If the CSRF token is missing
     */
    protected function getTokenFromRequest(Request $request): string
    {
        // Check for token in request data
        $token = $request->input('_token');
        
        // Check for token in headers
        if (empty($token)) {
            $token = $request->header('X-CSRF-TOKEN');
        }
        
        // Check for token in X-XSRF-TOKEN header (for JavaScript libraries)
        if (empty($token)) {
            $xsrfToken = $request->header('X-XSRF-TOKEN');
            if (!empty($xsrfToken)) {
                $token = urldecode($xsrfToken);
            }
        }
        
        // Throw an exception if the token is missing
        if (empty($token)) {
            throw new SecurityException('CSRF token not found in request.');
        }
        
        return $token;
    }
} 