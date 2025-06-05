<?php

namespace Portfolion\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;
use Twig\TwigFunction;
use Twig\Extension\ExtensionInterface;

class TwigTemplate
{
    private Environment $twig;
    
    public function __construct()
    {
        // Set up the loader to look in both resources/views and app/Views directories
        $loader = new FilesystemLoader([
            dirname(dirname(__DIR__)) . '/resources/views',
            dirname(dirname(__DIR__)) . '/app/Views'
        ]);
        
        // Configuration options
        $debug = $_ENV['APP_DEBUG'] ?? true;
        $cachePath = dirname(dirname(__DIR__)) . '/storage/cache/twig';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        
        $this->twig = new Environment($loader, [
            'cache' => $debug ? false : $cachePath,
            'auto_reload' => $debug,
            'debug' => $debug,
            'strict_variables' => $debug,
            'autoescape' => 'html',
        ]);
        
        // Add extensions and functions
        if ($debug) {
            $this->twig->addExtension(new DebugExtension());
        }
        
        // Add custom functions
        $this->addCustomFunctions();
    }
    
    /**
     * Add custom functions to Twig
     */
    private function addCustomFunctions(): void
    {
        // Add the asset function for linking to assets
        $this->twig->addFunction(new TwigFunction('asset', function ($path) {
            $path = ltrim($path, '/');
            $publicPath = dirname(dirname(__DIR__)) . '/public/assets/' . $path;
            
            // Add a timestamp for cache busting if the file exists
            if (file_exists($publicPath)) {
                return '/assets/' . $path . '?v=' . filemtime($publicPath);
            }
            
            return '/assets/' . $path;
        }));
        
        // Add the route function for generating URLs
        $this->twig->addFunction(new TwigFunction('route', function ($name, $params = []) {
            // Convert dot notation to path
            $path = str_replace('.', '/', $name);
            
            // If the path doesn't start with a slash, add one
            if (substr($path, 0, 1) !== '/') {
                $path = '/' . $path;
            }
            
            // Replace route parameters
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $path = str_replace('{' . $key . '}', $value, $path);
                }
            }
            
            return $path;
        }));
        
        // Add the csrf_token function
        $this->twig->addFunction(new TwigFunction('csrf_token', function () {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }));
    }
    
    /**
     * Render a template
     *
     * @param string $template Template name (without extension)
     * @param array $data Data to pass to the template
     * @return string Rendered template
     */
    public function render(string $template, array $data = []): string
    {
        // First try with .twig extension
        try {
            return $this->twig->render($template . '.twig', $data);
        } catch (\Twig\Error\LoaderError $e) {
            // If not found, try with .php extension
            try {
                return $this->twig->render($template . '.php', $data);
            } catch (\Twig\Error\LoaderError $e2) {
                // If still not found, throw the original exception
                throw $e;
            }
        }
    }
    
    /**
     * Get the Twig environment instance
     *
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
