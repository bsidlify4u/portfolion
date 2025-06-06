<?php

namespace Portfolion\View;

use Portfolion\Config\Config;

class ViewManager
{
    /**
     * @var ViewManager|null Singleton instance
     */
    private static ?ViewManager $instance = null;
    
    /**
     * @var array Registered view engines
     */
    private array $engines = [];
    
    /**
     * @var string Default engine name
     */
    private string $defaultEngine;
    
    /**
     * ViewManager constructor.
     */
    private function __construct()
    {
        $this->defaultEngine = Config::get('view.default', 'php');
        
        // Register default engines
        $this->registerEngine('php', new PhpViewEngine());
        
        // Register Twig if enabled
        if (Config::get('view.twig.enabled', true)) {
            $this->registerEngine('twig', new TwigViewEngine());
        }
        
        // Register Blade if enabled
        if (Config::get('view.blade.enabled', true)) {
            $this->registerEngine('blade', new BladeViewEngine());
        }
    }
    
    /**
     * Get the singleton instance
     *
     * @return ViewManager
     */
    public static function getInstance(): ViewManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Register a view engine
     *
     * @param string $name Engine name
     * @param ViewEngineInterface $engine Engine instance
     * @return void
     */
    public function registerEngine(string $name, ViewEngineInterface $engine): void
    {
        $this->engines[$name] = $engine;
    }
    
    /**
     * Get a view engine by name
     *
     * @param string|null $name Engine name (or null for default)
     * @return ViewEngineInterface
     * @throws \Exception If engine not found
     */
    public function getEngine(?string $name = null): ViewEngineInterface
    {
        $engineName = $name ?? $this->defaultEngine;
        
        if (!isset($this->engines[$engineName])) {
            throw new \Exception("View engine '{$engineName}' not found");
        }
        
        return $this->engines[$engineName];
    }
    
    /**
     * Render a view
     *
     * @param string $view View name
     * @param array $data View data
     * @param string|null $engine Engine name (or null for default)
     * @return string Rendered view
     */
    public function render(string $view, array $data = [], ?string $engine = null): string
    {
        // If the view has an extension, determine the engine from it
        if ($engine === null) {
            $extension = pathinfo($view, PATHINFO_EXTENSION);
            
            if ($extension === 'twig') {
                $engine = 'twig';
            } elseif ($extension === 'blade.php') {
                $engine = 'blade';
            }
        }
        
        return $this->getEngine($engine)->render($view, $data);
    }
    
    /**
     * Set the default view engine
     *
     * @param string $engine Engine name
     * @return void
     * @throws \Exception If engine not found
     */
    public function setDefaultEngine(string $engine): void
    {
        if (!isset($this->engines[$engine])) {
            throw new \Exception("View engine '{$engine}' not found");
        }
        
        $this->defaultEngine = $engine;
    }
} 