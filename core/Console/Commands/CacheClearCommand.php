<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;

class CacheClearCommand extends Command
{
    /**
     * Command name
     */
    protected string $name = 'cache:clear';
    
    /**
     * Command description
     */
    protected string $description = 'Clear the application cache';
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        try {
            // Clear file-based caches regardless of configuration
            $this->line("Clearing application caches...");
            
            // Clear storage/cache directory if it exists
            if (is_dir('storage/cache')) {
                $this->clearDirectory('storage/cache');
                $this->line("Application cache cleared!");
            }
            
            // Clear config cache if exists
            if (file_exists('storage/cache/config.php')) {
                unlink('storage/cache/config.php');
                $this->line("Configuration cache cleared!");
            }
            
            // Clear route cache if exists
            if (file_exists('storage/cache/routes.php')) {
                unlink('storage/cache/routes.php');
                $this->line("Route cache cleared!");
            }
            
            // Clear view cache if exists
            if (is_dir('storage/cache/views')) {
                $this->clearDirectory('storage/cache/views');
                $this->line("View cache cleared!");
            }
            
            // Try to clear cache using CacheManager if available
            try {
                // Include CacheManager only if it exists to avoid errors
                if (class_exists('\\Portfolion\\Cache\\CacheManager')) {
                    $cacheManager = \Portfolion\Cache\CacheManager::getInstance();
                    $cacheManager->store()->flush();
                    $this->line("Cache store flushed!");
                }
            } catch (\Exception $e) {
                // If CacheManager fails, just log and continue
                $this->warning("Could not flush cache store: " . $e->getMessage());
            }
            
            $this->info("Cache cleared successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Clear a directory's contents
     *
     * @param string $directory
     * @return void
     */
    protected function clearDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $path = $directory . '/' . $file;
            
            if (is_dir($path)) {
                $this->clearDirectory($path);
                if (!rmdir($path)) {
                    $this->warning("Could not remove directory: {$path}");
                }
            } else {
                if (!unlink($path)) {
                    $this->warning("Could not remove file: {$path}");
                }
            }
        }
    }
} 