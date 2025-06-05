<?php
namespace App\Middleware;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Cache\Cache;
use DateInterval;
use DateTime;
use RuntimeException;

class RateLimitMiddleware {
    private int $maxAttempts;
    private int $decayMinutes;
    private Cache $cache;
    
    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1) {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->cache = Cache::getInstance();
    }
    
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param \Closure $next
     * @return Response
     * @throws RuntimeException
     */
    public function handle(Request $request, \Closure $next): Response {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->tooManyAttempts($key)) {
            return $this->buildRateLimitExceededResponse($key);
        }
        
        $this->incrementAttempts($key);
        
        /** @var Response $response */
        $response = $next($request);
        
        return $this->addRateLimitHeaders($response, $key);
    }
    
    /**
     * Create a unique signature for the request.
     */
    protected function resolveRequestSignature(Request $request): string {
        if (!$ip = $request->getIp()) {
            throw new RuntimeException('Unable to generate the request signature. IP address not found.');
        }
        
        return sha1(sprintf(
            '%s|%s|%s',
            $request->getMethod(),
            $request->getPath(),
            $ip
        ));
    }
    
    /**
     * Determine if the given key has been "accessed" too many times.
     */
    protected function tooManyAttempts(string $key): bool {
        return $this->attempts($key) >= $this->maxAttempts;
    }
    
    /**
     * Get the number of attempts for the given key.
     */
    protected function attempts(string $key): int {
        return (int) $this->cache->get($key . ':attempts', 0);
    }
    
    /**
     * Increment the counter for a given key.
     */
    protected function incrementAttempts(string $key): void {
        $expiration = new DateTime();
        $expiration->add(new DateInterval("PT{$this->decayMinutes}M"));
        
        $attempts = $this->attempts($key) + 1;
        
        $this->cache->put(
            $key . ':attempts',
            $attempts,
            $expiration->getTimestamp() - time()
        );
        
        $this->cache->put(
            $key . ':timer',
            $expiration->getTimestamp(),
            $expiration->getTimestamp() - time()
        );
    }
    
    /**
     * Get the number of remaining attempts.
     */
    protected function remainingAttempts(string $key): int {
        return $this->maxAttempts - $this->attempts($key);
    }
    
    /**
     * Get the reset time for the given key.
     */
    protected function getTimeUntilReset(string $key): int {
        $resetTime = (int) $this->cache->get($key . ':timer', time() + 60);
        return max(0, $resetTime - time());
    }
    
    /**
     * Create a response when the rate limit is exceeded.
     */
    protected function buildRateLimitExceededResponse(string $key): Response {
        $response = new Response();
        $retryAfter = $this->getTimeUntilReset($key);
        
        return $this->addRateLimitHeaders($response, $key)
            ->setStatusCode(429)
            ->setContent([
                'error' => 'Too Many Attempts.',
                'retry_after' => $retryAfter
            ]);
    }
    
    /**
     * Add rate limit headers to the given response.
     */
    protected function addRateLimitHeaders(Response $response, string $key): Response {
        $remainingAttempts = $this->remainingAttempts($key);
        $response->addHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->addHeader('X-RateLimit-Remaining', (string) max(0, $remainingAttempts));
        
        if ($remainingAttempts <= 0) {
            $retryAfter = $this->getTimeUntilReset($key);
            $response->addHeader('Retry-After', (string) $retryAfter);
            $response->addHeader('X-RateLimit-Reset', (string) (time() + $retryAfter));
        }
        
        return $response;
    }
}
