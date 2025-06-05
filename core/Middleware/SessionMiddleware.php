<?php

namespace Portfolion\Middleware;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Session\SessionManager;

/**
 * Middleware for session management
 * 
 * This middleware initializes and manages the session for HTTP requests.
 */
class SessionMiddleware implements MiddlewareInterface
{
    /**
     * @var SessionManager Session manager instance
     */
    protected SessionManager $sessionManager;
    
    /**
     * Create a new session middleware instance
     */
    public function __construct()
    {
        $this->sessionManager = new SessionManager();
    }
    
    /**
     * Process the request and response
     * 
     * @param Request $request The HTTP request
     * @param callable $next The next middleware
     * @return Response The HTTP response
     */
    public function process(Request $request, callable $next): Response
    {
        // Start the session
        $this->sessionManager->start();
        
        // Process the request
        $response = $next($request);
        
        // Process flash data for the next request
        $this->processFlashData();
        
        return $response;
    }
    
    /**
     * Process flash data for the next request
     * 
     * @return void
     */
    protected function processFlashData(): void
    {
        $session = \Portfolion\Session\Session::getInstance();
        
        // Get current flash data
        $flash = $session->get('_flash', []);
        
        // Get old flash data
        $oldFlash = $session->get('_old_flash', []);
        
        // Remove old flash data
        foreach ($oldFlash as $key => $value) {
            $session->remove($key);
        }
        
        // Store current flash data for next request
        $session->set('_old_flash', $flash);
        
        // Clear current flash data
        $session->set('_flash', []);
    }
} 