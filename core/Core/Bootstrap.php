<?php

namespace Portfolion\Core;

use Portfolion\View\TwigTemplate;
use Portfolion\Routing\Router;
use Portfolion\Http\Response;
use Portfolion\Config;
use Portfolion\Security\SecurityMiddleware;
use Portfolion\Security\SecurityServiceProvider;
use Portfolion\Container\Container;
use Throwable;

class Bootstrap
{
    private static ?self $instance = null;
    private TwigTemplate $twig;
    private Router $router;
    private Config $config;
    private Container $container;
    private bool $hasBooted = false;

    private function __construct()
    {
        $this->container = Container::getInstance();
        $this->config = Config::getInstance();
        $this->initializeRouter();
        $this->twig = new TwigTemplate();
    }

    /**
     * Initialize the router.
     */
    private function initializeRouter(): void
    {
        $this->router = Router::getInstance();
        $this->container->instance(Router::class, $this->router);
        
        // Register default middleware
        $this->router->middleware([
            SecurityMiddleware::class,
            // Add other global middleware here
        ]);
        
        // Register middleware groups
        $this->router->middlewareGroup('web', [
            'cors',
            'session',
            'csrf',
            // Add other web middleware here
        ]);
        
        $this->router->middlewareGroup('api', [
            'cors',
            'throttle',
            'json',
            // Add other API middleware here
        ]);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getTwig(): TwigTemplate
    {
        return $this->twig;
    }

    public function boot(): void
    {
        if ($this->hasBooted) {
            return;
        }

        $this->registerExceptionHandler();
        SecurityServiceProvider::init();
        $this->hasBooted = true;
    }

    /**
     * Handle an exception and return a response.
     * 
     * @param Throwable $e
     * @return Response
     */
    public function handleException(Throwable $e): Response
    {
        $this->container->bind('Portfolion\\Exceptions\\Handler', 'Portfolion\\Exceptions\\Handler');
        $handler = $this->container->make('Portfolion\\Exceptions\\Handler');
        return $handler->handle($e);
    }

    /**
     * Register the global exception handler.
     */
    private function registerExceptionHandler(): void
    {
        $this->container->bind('Portfolion\\Exceptions\\Handler', 'Portfolion\\Exceptions\\Handler');
        
        set_exception_handler(function (Throwable $e) {
            $response = $this->handleException($e);
            if (!headers_sent()) {
                $response->send();
            } else {
                echo $response->getContent();
            }
        });
    }

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserializing of the singleton instance.
     * 
     * @throws \RuntimeException
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize a singleton.");
    }
}
