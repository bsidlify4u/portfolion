<?php

namespace Portfolion\View;

use Portfolion\Config;

/**
 * Asset Manager for handling CSS, JS, and image assets with versioning,
 * bundling, minification, and CDN integration capabilities.
 */
class AssetManager
{
    /** @var self|null */
    private static ?self $instance = null;
    
    /** @var array<string, array> */
    protected array $registeredAssets = [
        'css' => [],
        'js' => [],
        'inline_css' => [],
        'inline_js' => []
    ];
    
    /** @var array<string, string> */
    protected array $versionedAssets = [];
    
    /** @var string */
    protected string $publicPath;
    
    /** @var string */
    protected string $manifestPath;
    
    /** @var array|null */
    protected ?array $manifest = null;
    
    /** @var bool */
    protected bool $useManifest = false;
    
    /** @var bool */
    protected bool $enableMinification = false;
    
    /** @var string|null */
    protected ?string $cdnUrl = null;
    
    /** @var array<string, array> */
    protected array $bundleDefinitions = [];
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct()
    {
        $config = Config::getInstance();
        
        $this->publicPath = $config->get('app.public_path', public_path());
        $this->manifestPath = $this->publicPath . '/mix-manifest.json';
        $this->useManifest = file_exists($this->manifestPath);
        $this->enableMinification = $config->get('app.env') === 'production';
        $this->cdnUrl = $config->get('app.cdn_url');
        
        // Load bundle definitions
        $this->bundleDefinitions = $config->get('assets.bundles', []);
        
        // Load the manifest file if it exists
        if ($this->useManifest) {
            $this->loadManifest();
        }
    }
    
    /**
     * Load the asset manifest file
     */
    protected function loadManifest(): void
    {
        try {
            $content = file_get_contents($this->manifestPath);
            $this->manifest = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->manifest = null;
                $this->useManifest = false;
            }
        } catch (\Exception $e) {
            $this->manifest = null;
            $this->useManifest = false;
        }
    }
    
    /**
     * Register a CSS file
     * 
     * @param string $path Path to the CSS file
     * @param array $attributes Additional attributes for the link tag
     * @param int $priority Loading priority (lower values load first)
     * @return self
     */
    public function css(string $path, array $attributes = [], int $priority = 10): self
    {
        $this->registeredAssets['css'][] = [
            'path' => $path,
            'attributes' => $attributes,
            'priority' => $priority
        ];
        
        return $this;
    }
    
    /**
     * Register a JavaScript file
     * 
     * @param string $path Path to the JS file
     * @param bool $defer Whether to defer loading
     * @param bool $async Whether to load asynchronously
     * @param array $attributes Additional attributes
     * @param int $priority Loading priority (lower values load first)
     * @return self
     */
    public function js(string $path, bool $defer = true, bool $async = false, array $attributes = [], int $priority = 10): self
    {
        $this->registeredAssets['js'][] = [
            'path' => $path,
            'defer' => $defer,
            'async' => $async,
            'attributes' => $attributes,
            'priority' => $priority
        ];
        
        return $this;
    }
    
    /**
     * Add inline CSS
     * 
     * @param string $css CSS code
     * @param int $priority Loading priority (lower values load first)
     * @return self
     */
    public function inlineCss(string $css, int $priority = 10): self
    {
        $this->registeredAssets['inline_css'][] = [
            'content' => $css,
            'priority' => $priority
        ];
        
        return $this;
    }
    
    /**
     * Add inline JavaScript
     * 
     * @param string $js JavaScript code
     * @param int $priority Loading priority (lower values load first) 
     * @return self
     */
    public function inlineJs(string $js, int $priority = 10): self
    {
        $this->registeredAssets['inline_js'][] = [
            'content' => $js,
            'priority' => $priority
        ];
        
        return $this;
    }
    
    /**
     * Register a predefined bundle of assets
     * 
     * @param string $bundleName Name of the bundle defined in config
     * @return self
     */
    public function bundle(string $bundleName): self
    {
        if (!isset($this->bundleDefinitions[$bundleName])) {
            return $this;
        }
        
        $bundle = $this->bundleDefinitions[$bundleName];
        
        // Register all assets in the bundle
        if (isset($bundle['css']) && is_array($bundle['css'])) {
            foreach ($bundle['css'] as $css) {
                $path = $css['path'] ?? $css;
                $attributes = $css['attributes'] ?? [];
                $priority = $css['priority'] ?? 10;
                
                $this->css($path, $attributes, $priority);
            }
        }
        
        if (isset($bundle['js']) && is_array($bundle['js'])) {
            foreach ($bundle['js'] as $js) {
                $path = $js['path'] ?? $js;
                $defer = $js['defer'] ?? true;
                $async = $js['async'] ?? false;
                $attributes = $js['attributes'] ?? [];
                $priority = $js['priority'] ?? 10;
                
                $this->js($path, $defer, $async, $attributes, $priority);
            }
        }
        
        return $this;
    }
    
    /**
     * Get the versioned URL for an asset
     * 
     * @param string $path Original asset path
     * @return string Versioned asset URL
     */
    public function versionedUrl(string $path): string
    {
        // Check if we've already processed this asset
        if (isset($this->versionedAssets[$path])) {
            return $this->versionedAssets[$path];
        }
        
        // Clean the path from leading slash
        $path = ltrim($path, '/');
        
        // Use manifest if available
        if ($this->useManifest && $this->manifest) {
            $manifestPath = '/' . $path;
            if (isset($this->manifest[$manifestPath])) {
                $versionedPath = $this->manifest[$manifestPath];
                $result = $this->applyBasePath($versionedPath);
                $this->versionedAssets[$path] = $result;
                return $result;
            }
        }
        
        // Fallback to query string versioning
        $timestamp = $this->getFileTimestamp($path);
        $result = $this->applyBasePath('/' . $path) . ($timestamp ? "?v={$timestamp}" : '');
        $this->versionedAssets[$path] = $result;
        return $result;
    }
    
    /**
     * Apply the base path (CDN or local) to the asset path
     * 
     * @param string $path Asset path
     * @return string Complete asset URL
     */
    protected function applyBasePath(string $path): string
    {
        // If CDN is enabled, use the CDN URL
        if ($this->cdnUrl) {
            return rtrim($this->cdnUrl, '/') . '/' . ltrim($path, '/');
        }
        
        return $path;
    }
    
    /**
     * Get the file modification timestamp for versioning
     * 
     * @param string $path Asset path relative to public directory
     * @return int|null File modification timestamp or null if file doesn't exist
     */
    protected function getFileTimestamp(string $path): ?int
    {
        $fullPath = $this->publicPath . '/' . ltrim($path, '/');
        
        if (file_exists($fullPath)) {
            return filemtime($fullPath);
        }
        
        return null;
    }
    
    /**
     * Generate HTML for all registered CSS assets
     * 
     * @return string HTML for CSS assets
     */
    public function renderCss(): string
    {
        if (empty($this->registeredAssets['css'])) {
            return '';
        }
        
        // Sort by priority
        $assets = $this->registeredAssets['css'];
        usort($assets, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        $html = '';
        
        // Render link tags
        foreach ($assets as $asset) {
            $url = $this->versionedUrl($asset['path']);
            
            $attributesHtml = '';
            foreach ($asset['attributes'] as $name => $value) {
                $attributesHtml .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
            }
            
            $html .= '<link rel="stylesheet" href="' . $url . '"' . $attributesHtml . '>' . PHP_EOL;
        }
        
        // Render inline CSS
        if (!empty($this->registeredAssets['inline_css'])) {
            $inlineCss = $this->registeredAssets['inline_css'];
            usort($inlineCss, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            
            $html .= '<style>' . PHP_EOL;
            foreach ($inlineCss as $css) {
                $html .= $css['content'] . PHP_EOL;
            }
            $html .= '</style>' . PHP_EOL;
        }
        
        return $html;
    }
    
    /**
     * Generate HTML for all registered JavaScript assets
     * 
     * @return string HTML for JavaScript assets
     */
    public function renderJs(): string
    {
        if (empty($this->registeredAssets['js'])) {
            return '';
        }
        
        // Sort by priority
        $assets = $this->registeredAssets['js'];
        usort($assets, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        $html = '';
        
        // Render script tags
        foreach ($assets as $asset) {
            $url = $this->versionedUrl($asset['path']);
            
            $attributesHtml = '';
            if ($asset['defer']) {
                $attributesHtml .= ' defer';
            }
            if ($asset['async']) {
                $attributesHtml .= ' async';
            }
            
            foreach ($asset['attributes'] as $name => $value) {
                $attributesHtml .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
            }
            
            $html .= '<script src="' . $url . '"' . $attributesHtml . '></script>' . PHP_EOL;
        }
        
        // Render inline JavaScript
        if (!empty($this->registeredAssets['inline_js'])) {
            $inlineJs = $this->registeredAssets['inline_js'];
            usort($inlineJs, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            
            $html .= '<script>' . PHP_EOL;
            foreach ($inlineJs as $js) {
                $html .= $js['content'] . PHP_EOL;
            }
            $html .= '</script>' . PHP_EOL;
        }
        
        return $html;
    }
    
    /**
     * Clear all registered assets
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->registeredAssets = [
            'css' => [],
            'js' => [],
            'inline_css' => [],
            'inline_js' => []
        ];
        
        return $this;
    }
    
    /**
     * Get the singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Create a new instance (useful for testing)
     * 
     * @return self
     */
    public static function newInstance(): self
    {
        return new self();
    }
} 