<?php

namespace Portfolion\View;

use Portfolion\Config\Config;

class PhpViewEngine implements ViewEngineInterface
{
    /**
     * @var array View paths
     */
    private array $paths = [];
    
    /**
     * @var array Shared data
     */
    private array $shared = [];
    
    /**
     * PhpViewEngine constructor.
     */
    public function __construct()
    {
        // Add default view path
        $this->addPath(Config::get('view.paths.php', 'resources/views'));
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
        $path = $this->findView($view);
        
        if ($path === null) {
            throw new \Exception("View '{$view}' not found");
        }
        
        // Extract shared data
        $data = array_merge($this->shared, $data);
        
        // Start output buffering
        ob_start();
        
        // Extract data to make it available in the view
        extract($data);
        
        // Include the view file
        include $path;
        
        // Get the buffered content
        return ob_get_clean();
    }
    
    /**
     * Check if a view exists
     *
     * @param string $view View name
     * @return bool
     */
    public function exists(string $view): bool
    {
        return $this->findView($view) !== null;
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
    }
    
    /**
     * Add a view path
     *
     * @param string $path Path to views
     * @return void
     */
    public function addPath(string $path): void
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
    }
    
    /**
     * Find a view file
     *
     * @param string $view View name
     * @return string|null Path to view file or null if not found
     */
    private function findView(string $view): ?string
    {
        // Add .php extension if not present
        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }
        
        // Replace dots with directory separators
        $view = str_replace('.', '/', $view);
        
        // Look for the view in all paths
        foreach ($this->paths as $path) {
            $viewPath = $path . '/' . $view;
            
            if (file_exists($viewPath)) {
                return $viewPath;
            }
        }
        
        return null;
    }
} 