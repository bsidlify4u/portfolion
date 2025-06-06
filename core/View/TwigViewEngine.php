<?php

namespace Portfolion\View;

use Portfolion\Config\Config;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Twig\Extension\DebugExtension;

class TwigViewEngine implements ViewEngineInterface
{
    /**
     * @var FilesystemLoader Twig loader
     */
    private FilesystemLoader $loader;
    
    /**
     * @var Environment Twig environment
     */
    private Environment $twig;
    
    /**
     * @var array Shared data
     */
    private array $shared = [];
    
    /**
     * TwigViewEngine constructor.
     */
    public function __construct()
    {
        // Create Twig loader
        $this->loader = new FilesystemLoader();
        
        // Add default view path
        $this->addPath(Config::get('view.paths.twig', 'resources/views'));
        
        // Create Twig environment
        $this->twig = new Environment($this->loader, [
            'cache' => Config::get('view.twig.cache', false) ? 'storage/cache/twig' : false,
            'debug' => Config::get('view.twig.debug', Config::get('app.debug', false)),
            'auto_reload' => Config::get('view.twig.auto_reload', true),
            'strict_variables' => Config::get('view.twig.strict_variables', false),
        ]);
        
        // Add debug extension if debug is enabled
        if (Config::get('view.twig.debug', Config::get('app.debug', false))) {
            $this->twig->addExtension(new DebugExtension());
        }
        
        // Add custom extensions
        $this->registerExtensions();
        
        // Register global functions
        $this->registerGlobalFunctions();
    }
    
    /**
     * Render a view
     *
     * @param string $view View name
     * @param array $data View data
     * @return string Rendered view
     * @throws \Exception If view not found
     */
    public function render(string $view, array $data = []): string
    {
        // Add .twig extension if not present
        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }
        
        // Replace dots with directory separators
        $view = str_replace('.', '/', $view);
        
        // Merge shared data with view data
        $data = array_merge($this->shared, $data);
        
        try {
            return $this->twig->render($view, $data);
        } catch (\Twig\Error\LoaderError $e) {
            throw new \Exception("View '{$view}' not found: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception("Error rendering view '{$view}': " . $e->getMessage());
        }
    }
    
    /**
     * Check if a view exists
     *
     * @param string $view View name
     * @return bool
     */
    public function exists(string $view): bool
    {
        // Add .twig extension if not present
        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }
        
        // Replace dots with directory separators
        $view = str_replace('.', '/', $view);
        
        try {
            return $this->loader->exists($view);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Share data with all views
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function share(string $key, $value): void
    {
        $this->shared[$key] = $value;
        $this->twig->addGlobal($key, $value);
    }
    
    /**
     * Add a view path
     *
     * @param string $path Path to views
     * @return void
     */
    public function addPath(string $path): void
    {
        try {
            $this->loader->addPath($path);
        } catch (\Exception $e) {
            // Ignore if path doesn't exist
        }
    }
    
    /**
     * Get the Twig environment
     *
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
    
    /**
     * Register a Twig function
     *
     * @param string $name Function name
     * @param callable $callback Function callback
     * @param array $options Function options
     * @return void
     */
    public function registerFunction(string $name, callable $callback, array $options = []): void
    {
        $this->twig->addFunction(new TwigFunction($name, $callback, $options));
    }
    
    /**
     * Register custom extensions
     *
     * @return void
     */
    private function registerExtensions(): void
    {
        // Register model access extension
        $this->twig->addExtension(new TwigModelAccessExtension());
        
        // Register other extensions as needed
    }
    
    /**
     * Register global functions
     *
     * @return void
     */
    private function registerGlobalFunctions(): void
    {
        // Register common functions
        $this->registerFunction('url', function ($path = '') {
            return Config::get('app.url', '') . '/' . ltrim($path, '/');
        });
        
        $this->registerFunction('asset', function ($path) {
            return Config::get('app.url', '') . '/assets/' . ltrim($path, '/');
        });
        
        $this->registerFunction('config', function ($key, $default = null) {
            return Config::get($key, $default);
        });
        
        $this->registerFunction('csrf_field', function () {
            return '<input type="hidden" name="_token" value="' . $_SESSION['_token'] ?? '' . '">';
        });
        
        $this->registerFunction('method_field', function ($method) {
            return '<input type="hidden" name="_method" value="' . $method . '">';
        });
        
        // Register dump function if debug is enabled
        if (Config::get('view.twig.debug', Config::get('app.debug', false))) {
            $this->registerFunction('dump', function () {
                ob_start();
                call_user_func_array('var_dump', func_get_args());
                return ob_get_clean();
            }, ['is_safe' => ['html']]);
        }
    }
} 