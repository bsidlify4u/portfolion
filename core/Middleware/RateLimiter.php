<?php

namespace Portfolion\Middleware;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Session\Session;

/**
 * Rate limiting middleware to prevent abuse
 */
class RateLimiter
{
    /**
     * Maximum number of requests allowed within the time window
     */
    protected int $maxRequests;
    
    /**
     * Time window in seconds
     */
    protected int $timeWindow;
    
    /**
     * Session key to store rate limit data
     */
    protected string $sessionKey = 'rate_limit';
    
    /**
     * Create a new rate limiter instance
     * 
     * @param int $maxRequests Maximum number of requests allowed within the time window
     * @param int $timeWindow Time window in seconds
     */
    public function __construct(int $maxRequests = 60, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    /**
     * Handle the incoming request
     * 
     * @param Request $request The HTTP request
     * @param callable $next The next middleware
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $session = Session::getInstance();
        
        // Get current time
        $now = time();
        
        // Get rate limit data from session
        $rateLimitData = $session->get($this->sessionKey, [
            'requests' => [],
            'blocked_until' => null
        ]);
        
        // Check if currently blocked
        if ($rateLimitData['blocked_until'] !== null && $now < $rateLimitData['blocked_until']) {
            $retryAfter = $rateLimitData['blocked_until'] - $now;
            
            // Return 429 Too Many Requests
            $response = new Response('Too Many Requests', 429);
            $response->addHeader('Retry-After', (string) $retryAfter);
            return $response;
        }
        
        // Clean up old requests outside the time window
        $rateLimitData['requests'] = array_filter(
            $rateLimitData['requests'],
            fn($timestamp) => $timestamp > ($now - $this->timeWindow)
        );
        
        // Add current request
        $rateLimitData['requests'][] = $now;
        
        // Check if too many requests
        if (count($rateLimitData['requests']) > $this->maxRequests) {
            // Block for 5 minutes
            $rateLimitData['blocked_until'] = $now + 300;
            
            // Save updated rate limit data
            $session->set($this->sessionKey, $rateLimitData);
            
            // Return 429 Too Many Requests
            $response = new Response('Too Many Requests', 429);
            $response->addHeader('Retry-After', '300');
            return $response;
        }
        
        // Save updated rate limit data
        $session->set($this->sessionKey, $rateLimitData);
        
        // Add rate limit headers to response
        $response = $next($request);
        $response->addHeader('X-RateLimit-Limit', (string) $this->maxRequests);
        $response->addHeader('X-RateLimit-Remaining', (string) ($this->maxRequests - count($rateLimitData['requests'])));
        $response->addHeader('X-RateLimit-Reset', (string) ($now + $this->timeWindow));
        
        return $response;
    }
} 