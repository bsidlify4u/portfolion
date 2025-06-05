<?php

namespace Portfolion\View;

use Portfolion\Config;
use RuntimeException;

/**
 * Asset compiler for bundling and minification of CSS, JS, and other assets.
 */
class AssetCompiler
{
    /** @var Config */
    protected Config $config;
    
    /** @var array */
    protected array $compilationConfig;
    
    /** @var array */
    protected array $manifest = [];
    
    /** @var string */
    protected string $manifestPath;
    
    /**
     * Create a new asset compiler instance
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->compilationConfig = $this->config->get('assets.compilation', []);
        $this->manifestPath = $this->compilationConfig['manifest_path'] ?? public_path('mix-manifest.json');
        
        // Load existing manifest if it exists
        if (file_exists($this->manifestPath)) {
            $manifestContent = file_get_contents($this->manifestPath);
            $this->manifest = json_decode($manifestContent, true) ?? [];
        }
    }
    
    /**
     * Compile all assets
     * 
     * @return bool Success indicator
     */
    public function compileAll(): bool
    {
        if (!($this->compilationConfig['enabled'] ?? false)) {
            return false;
        }
        
        $success = true;
        
        // Compile SASS/SCSS files
        if ($this->compilationConfig['sass']['enabled'] ?? false) {
            $success = $this->compileSass() && $success;
        }
        
        // Compile JavaScript files
        if ($this->compilationConfig['js']['enabled'] ?? false) {
            $success = $this->compileJs() && $success;
        }
        
        // Save the manifest file
        if ($success) {
            $this->saveManifest();
        }
        
        return $success;
    }
    
    /**
     * Compile SASS/SCSS files
     * 
     * @return bool Success indicator
     */
    protected function compileSass(): bool
    {
        $sourceDir = $this->compilationConfig['sass']['source_dir'] ?? '';
        $outputDir = $this->compilationConfig['sass']['output_dir'] ?? '';
        
        if (!$sourceDir || !$outputDir || !is_dir($sourceDir)) {
            return false;
        }
        
        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Find all .scss and .sass files in the source directory
        $files = $this->findFiles($sourceDir, ['scss', 'sass']);
        
        // Check if we have sass installed
        $this->checkSassInstallation();
        
        foreach ($files as $file) {
            // Skip files starting with underscore (partials)
            if (basename($file)[0] === '_') {
                continue;
            }
            
            $inputPath = $file;
            $outputPath = $outputDir . '/' . $this->getOutputFilename($file, 'css');
            
            // Determine if we should minify
            $minify = $this->shouldMinify('css');
            
            // Generate source maps in development
            $sourceMap = $this->compilationConfig['sass']['options']['source_maps'] ?? false;
            
            // Compile SASS to CSS
            $cmd = "sass {$inputPath} {$outputPath}";
            
            // Add options
            if ($minify) {
                $cmd .= " --style=compressed";
            }
            
            if ($sourceMap) {
                $cmd .= " --source-map";
            }
            
            if ($this->compilationConfig['sass']['options']['autoprefixer'] ?? false) {
                // We need to use postcss with autoprefixer afterward
                $tempFile = $outputPath . '.temp';
                rename($outputPath, $tempFile);
                
                $postcssCmd = "postcss {$tempFile} --use autoprefixer -o {$outputPath}";
                
                if ($sourceMap) {
                    $postcssCmd .= " --map";
                }
                
                $this->runCommand($postcssCmd);
                
                // Remove temp file
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            
            // Add to manifest
            $this->addToManifest($inputPath, $outputPath);
        }
        
        return true;
    }
    
    /**
     * Compile JavaScript files
     * 
     * @return bool Success indicator
     */
    protected function compileJs(): bool
    {
        $sourceDir = $this->compilationConfig['js']['source_dir'] ?? '';
        $outputDir = $this->compilationConfig['js']['output_dir'] ?? '';
        
        if (!$sourceDir || !$outputDir || !is_dir($sourceDir)) {
            return false;
        }
        
        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Find all .js files in the source directory
        $files = $this->findFiles($sourceDir, ['js']);
        
        foreach ($files as $file) {
            $inputPath = $file;
            $outputPath = $outputDir . '/' . $this->getOutputFilename($file, 'js');
            
            // Determine if we should minify
            $minify = $this->shouldMinify('js');
            
            // Generate source maps in development
            $sourceMap = $this->compilationConfig['js']['options']['source_maps'] ?? false;
            
            // Determine if we should use Babel
            $useBabel = $this->compilationConfig['js']['babel'] ?? false;
            
            if ($useBabel) {
                // Check if babel is installed
                $this->checkBabelInstallation();
                
                // Compile with Babel
                $cmd = "babel {$inputPath} --out-file {$outputPath}";
                
                if ($sourceMap) {
                    $cmd .= " --source-maps";
                }
                
                if ($minify) {
                    $cmd .= " --minified";
                }
                
                $this->runCommand($cmd);
            } else {
                // Simple copy if no compilation needed
                copy($inputPath, $outputPath);
                
                // Minify if needed
                if ($minify) {
                    $this->minifyJs($outputPath);
                }
            }
            
            // Add to manifest
            $this->addToManifest($inputPath, $outputPath);
        }
        
        return true;
    }
    
    /**
     * Check if Sass is installed
     * 
     * @throws RuntimeException If Sass is not installed
     */
    protected function checkSassInstallation(): void
    {
        $output = [];
        exec('sass --version 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new RuntimeException(
                "Sass is not installed. Please install it with npm: npm install -g sass"
            );
        }
    }
    
    /**
     * Check if Babel is installed
     * 
     * @throws RuntimeException If Babel is not installed
     */
    protected function checkBabelInstallation(): void
    {
        $output = [];
        exec('babel --version 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new RuntimeException(
                "Babel is not installed. Please install it with npm: npm install -g @babel/cli @babel/core @babel/preset-env"
            );
        }
    }
    
    /**
     * Run a command and handle errors
     * 
     * @param string $command Command to run
     * @return string Command output
     * @throws RuntimeException If command fails
     */
    protected function runCommand(string $command): string
    {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        $outputString = implode("\n", $output);
        
        if ($returnCode !== 0) {
            throw new RuntimeException("Command failed: {$command}\nOutput: {$outputString}");
        }
        
        return $outputString;
    }
    
    /**
     * Minify a JavaScript file
     * 
     * @param string $filePath Path to the JavaScript file
     * @return bool Success indicator
     */
    protected function minifyJs(string $filePath): bool
    {
        // Check if we have a minifier available
        if (class_exists('\MatthiasMullie\Minify\JS')) {
            // Use the minifier if available
            $minifier = new \MatthiasMullie\Minify\JS($filePath);
            $minifier->minify($filePath);
            return true;
        }
        
        // Fallback to terser if available
        $output = [];
        exec('terser --version 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            $tempFile = $filePath . '.temp';
            rename($filePath, $tempFile);
            
            $cmd = "terser {$tempFile} -o {$filePath} --compress --mangle";
            $this->runCommand($cmd);
            
            // Remove temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return true;
        }
        
        // If no minifier is available, just return the file as is
        return false;
    }
    
    /**
     * Get the output filename for an asset file
     * 
     * @param string $inputPath Input file path
     * @param string $extension New extension (without dot)
     * @return string Output filename
     */
    protected function getOutputFilename(string $inputPath, string $extension): string
    {
        $filename = basename($inputPath);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        $strategy = $this->config->get('assets.cache_busting.strategy', 'query');
        
        if ($strategy === 'filename' && $this->shouldMinify($extension)) {
            // Add hash to filename for cache busting
            $hash = substr(md5_file($inputPath), 0, $this->config->get('assets.cache_busting.length', 8));
            return "{$basename}.{$hash}.{$extension}";
        }
        
        return "{$basename}.{$extension}";
    }
    
    /**
     * Add a file to the manifest
     * 
     * @param string $inputPath Original file path
     * @param string $outputPath Compiled file path
     */
    protected function addToManifest(string $inputPath, string $outputPath): void
    {
        // Convert to relative paths
        $publicPath = $this->config->get('assets.public_path', public_path());
        
        $inputRelative = str_replace($publicPath, '', $inputPath);
        $outputRelative = str_replace($publicPath, '', $outputPath);
        
        // Add leading slash if missing
        if (substr($inputRelative, 0, 1) !== '/') {
            $inputRelative = '/' . $inputRelative;
        }
        
        if (substr($outputRelative, 0, 1) !== '/') {
            $outputRelative = '/' . $outputRelative;
        }
        
        $this->manifest[$inputRelative] = $outputRelative;
    }
    
    /**
     * Save the manifest file
     */
    protected function saveManifest(): void
    {
        $json = json_encode($this->manifest, JSON_PRETTY_PRINT);
        file_put_contents($this->manifestPath, $json);
    }
    
    /**
     * Find all files with specific extensions in a directory (recursive)
     * 
     * @param string $directory Directory to search in
     * @param array $extensions File extensions to look for
     * @return array List of file paths
     */
    protected function findFiles(string $directory, array $extensions): array
    {
        $files = [];
        
        foreach ($extensions as $ext) {
            $pattern = rtrim($directory, '/') . "/**/*.{$ext}";
            $files = array_merge($files, glob($pattern, GLOB_BRACE));
        }
        
        return $files;
    }
    
    /**
     * Determine if we should minify a specific file type
     * 
     * @param string $type File type (css or js)
     * @return bool Whether to minify
     */
    protected function shouldMinify(string $type): bool
    {
        $minifyConfig = $this->config->get('assets.minify', []);
        
        return ($minifyConfig['enabled'] ?? false) && ($minifyConfig[$type] ?? false);
    }
} 