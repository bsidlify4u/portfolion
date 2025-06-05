<?php

namespace Portfolion\Http\Middleware;

use Closure;
use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Config;
use Portfolion\Cache\Cache;

/**
 * Advanced Rate Limiter middleware for API throttling
 */
class RateLimiter
{
    /**
     * The cache instance
     * 
     * @var Cache
     */
    protected Cache $cache;
    
    /**
     * The configuration instance
     * 
     * @var Config
     */
    protected Config $config;
    
    /**
     * Create a new rate limiter middleware instance
     * 
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->config = Config::getInstance();
    }
    
    /**
     * Handle an incoming request
     * 
     * @param Request $request
     * @param Closure $next
     * @param string $limiterName Name of the rate limiter configuration to use
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'default'): Response
    {
        // Check if rate limiting is enabled
        if (!$this->config->get('api.rate_limiting.enabled', true)) {
            return $next($request);
        }
        
        // Get rate limiting configuration
        $limiterConfig = $this->getLimiterConfig($limiterName);
        $maxAttempts = $limiterConfig['max_attempts'] ?? 60;
        $decayMinutes = $limiterConfig['decay_minutes'] ?? 1;
        $prefix = $limiterConfig['prefix'] ?? 'portfolion:ratelimit:';
        
        // Get the key for the limiter
        $key = $this->resolveRequestSignature($request, $limiterConfig);
        
        // Resolve decay time to seconds
        $decaySeconds = $decayMinutes * 60;
        
        // Increment attempts for this key
        $attempts = $this->cache->increment("{$prefix}{$key}", 1);
        
        // If this is the first attempt for this key, set expiry
        if ($attempts === 1) {
            $this->cache->put("{$prefix}{$key}", 1, $decaySeconds);
        }
        
        // Check if we should allow the request
        if ($attempts <= $maxAttempts) {
            $response = $next($request);
            
            // Add rate limit headers to the response
            return $this->addHeaders(
                $response,
                $maxAttempts,
                $attempts,
                $this->calculateRemainingAttempts($maxAttempts, $attempts),
                $this->calculateRetryAfter($key, $prefix, $decaySeconds)
            );
        }
        
        // Request has been rate limited
        return $this->buildRateLimitedResponse(
            $key,
            $prefix,
            $maxAttempts,
            $decaySeconds,
            $limiterConfig
        );
    }
    
    /**
     * Get the rate limiter configuration
     * 
     * @param string $limiterName
     * @return array
     */
    protected function getLimiterConfig(string $limiterName): array
    {
        $limiters = $this->config->get('api.rate_limiting.limiters', []);
        
        // Default limiter config
        $defaultConfig = [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'by' => 'ip',
            'prefix' => 'portfolion:ratelimit:',
            'response_message' => 'Too Many Attempts.',
            'response_status' => 429,
        ];
        
        // Get the specified limiter or fall back to default
        $limiterConfig = $limiters[$limiterName] ?? $defaultConfig;
        
        // Merge with default values for any missing keys
        return array_merge($defaultConfig, $limiterConfig);
    }
    
    /**
     * Resolve the request signature based on configuration
     * 
     * @param Request $request
     * @param array $limiterConfig
     * @return string
     */
    protected function resolveRequestSignature(Request $request, array $limiterConfig): string
    {
        $by = $limiterConfig['by'] ?? 'ip';
        
        // Build signature parts
        $parts = [];
        
        // Add limiter-specific identifier
        switch ($by) {
            case 'ip':
                $parts[] = $request->ip();
                break;
            case 'user':
                $userId = $request->user() ? $request->user()->getId() : 'guest';
                $parts[] = $userId;
                break;
            case 'api_key':
                $parts[] = $request->header('X-API-Key') ?: 'no-key';
                break;
            case 'route':
                $parts[] = $request->route()->getName() ?: $request->getPathInfo();
                break;
            default:
                $parts[] = $request->ip();
        }
        
        // Add additional parts if configured
        if (isset($limiterConfig['include_method']) && $limiterConfig['include_method']) {
            $parts[] = $request->getMethod();
        }
        
        if (isset($limiterConfig['include_route']) && $limiterConfig['include_route']) {
            $parts[] = $request->getPathInfo();
        }
        
        // Custom signature generator
        if (isset($limiterConfig['using']) && is_callable($limiterConfig['using'])) {
            return call_user_func($limiterConfig['using'], $request);
        }
        
        return implode('|', $parts);
    }
    
    /**
     * Add rate limit headers to a response
     * 
     * @param Response $response
     * @param int $maxAttempts
     * @param int $attempts
     * @param int $remainingAttempts
     * @param int $retryAfter
     * @return Response
     */
    protected function addHeaders(
        Response $response,
        int $maxAttempts,
        int $attempts,
        int $remainingAttempts,
        int $retryAfter
    ): Response {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            'X-RateLimit-Used' => $attempts,
            'X-RateLimit-Reset' => $this->availableAt($retryAfter),
        ]);
    }
    
    /**
     * Calculate the remaining number of attempts
     * 
     * @param int $maxAttempts
     * @param int $attempts
     * @return int
     */
    protected function calculateRemainingAttempts(int $maxAttempts, int $attempts): int
    {
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * Calculate the "retry after" timestamp
     * 
     * @param string $key
     * @param string $prefix
     * @param int $decaySeconds
     * @return int
     */
    protected function calculateRetryAfter(string $key, string $prefix, int $decaySeconds): int
    {
        // Get the expiry time of the key
        $ttl = $this->cache->ttl("{$prefix}{$key}");
        
        // If TTL is not available, use decay seconds
        return $ttl > 0 ? $ttl : $decaySeconds;
    }
    
    /**
     * Get the timestamp when the rate limit will be available again
     * 
     * @param int $retryAfter Seconds to retry after
     * @return int Timestamp
     */
    protected function availableAt(int $retryAfter): int
    {
        return time() + $retryAfter;
    }
    
    /**
     * Build a rate limited response
     * 
     * @param string $key
     * @param string $prefix
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @param array $limiterConfig
     * @return Response
     */
    protected function buildRateLimitedResponse(
        string $key,
        string $prefix,
        int $maxAttempts,
        int $decaySeconds,
        array $limiterConfig
    ): Response {
        $retryAfter = $this->calculateRetryAfter($key, $prefix, $decaySeconds);
        $message = $limiterConfig['response_message'] ?? 'Too Many Attempts.';
        $status = $limiterConfig['response_status'] ?? 429;
        
        $response = new Response(json_encode([
            'success' => false,
            'message' => $message,
            'retry_after' => $retryAfter,
            'status' => $status,
        ]), $status);
        
        $response = $response->withHeaders([
            'Content-Type' => 'application/json',
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => $this->availableAt($retryAfter),
        ]);
        
        return $response;
    }
} 