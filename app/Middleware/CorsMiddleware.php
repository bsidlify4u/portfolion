<?php
namespace App\Middleware;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Config;

class CorsMiddleware {
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next): Response {
        $config = Config::getInstance();
        $corsConfig = $config->get('security.cors', [
            'allowed_origins' => [],
            'supports_credentials' => false,
            'allowed_methods' => [],
            'allowed_headers' => [],
            'expose_headers' => [],
            'max_age' => 0,
        ]);
        
        $response = new Response();
        
        // Access-Control-Allow-Origin
        $origin = $request->getHeader('Origin');
        if (!empty($origin) && 
            (in_array('*', $corsConfig['allowed_origins'], true) || 
            in_array($origin, $corsConfig['allowed_origins'], true))) {
            $response->addHeader('Access-Control-Allow-Origin', $origin);
        }
        
        // Access-Control-Allow-Credentials
        if ($corsConfig['supports_credentials']) {
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            // Access-Control-Allow-Methods
            if (!empty($corsConfig['allowed_methods'])) {
                $response->addHeader(
                    'Access-Control-Allow-Methods', 
                    implode(', ', array_map('strtoupper', $corsConfig['allowed_methods']))
                );
            }
            
            // Access-Control-Allow-Headers
            if (!empty($corsConfig['allowed_headers'])) {
                $response->addHeader(
                    'Access-Control-Allow-Headers', 
                    implode(', ', array_map('strtolower', $corsConfig['allowed_headers']))
                );
            }
            
            // Access-Control-Expose-Headers
            if (!empty($corsConfig['expose_headers'])) {
                $response->addHeader(
                    'Access-Control-Expose-Headers', 
                    implode(', ', array_map('strtolower', $corsConfig['expose_headers']))
                );
            }
            
            // Access-Control-Max-Age
            if ($corsConfig['max_age'] > 0) {
                $response->addHeader('Access-Control-Max-Age', (string) $corsConfig['max_age']);
            }
            
            return $response->setStatusCode(204);
        }
        
        $nextResponse = $next($request);
        
        // Transfer CORS headers to the actual response
        foreach ($response->getHeaders() as $name => $value) {
            $nextResponse->addHeader($name, $value);
        }
        
        return $nextResponse;
    }
}
