<?php

namespace Portfolion\Providers;

use Portfolion\App;
use Portfolion\Config;
use Portfolion\Session\SessionManager;
use Portfolion\Middleware\SessionMiddleware;
use Portfolion\Middleware\CsrfMiddleware;

/**
 * Service provider for session services
 * 
 * This provider registers session-related services and middleware.
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    /**
     * @var App Application instance
     */
    protected App $app;
    
    /**
     * @var Config Configuration instance
     */
    protected Config $config;
    
    /**
     * Register the service provider
     * 
     * @param App $app Application instance
     * @return void
     */
    public function register(App $app): void
    {
        $this->app = $app;
        $this->config = Config::getInstance();
        
        // Register session manager
        $app->singleton(SessionManager::class, function () {
            return new SessionManager();
        });
        
        // Register session middleware
        $app->middleware(SessionMiddleware::class);
        
        // Register CSRF middleware if enabled
        $env = $this->config->get('env.current', 'local');
        $envConfig = $this->config->get('env.environments.' . $env, []);
        
        if ($envConfig['csrf_protection'] ?? true) {
            $except = $this->config->get('security.csrf.except', []);
            $app->middleware(CsrfMiddleware::class, [$except]);
        }
    }
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot(): void
    {
        // Start the session for console commands if needed
        if (PHP_SAPI !== 'cli' && $this->config->get('session.start_automatically', true)) {
            $sessionManager = $this->app->make(SessionManager::class);
            $sessionManager->start();
        }
    }
} 